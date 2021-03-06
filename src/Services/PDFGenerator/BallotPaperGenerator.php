<?php


namespace App\Services\PDFGenerator;


use App\Entity\PostalVotingRegistration;
use Dompdf\Dompdf;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use iio\libmergepdf\Merger;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class BallotPaperGenerator
{
    private $twig;
    private $urlGenerator;
    private $cacheDir;

    public function __construct(Environment $twig, UrlGeneratorInterface $urlGenerator, KernelInterface $kernel)
    {
        $this->twig = $twig;
        $this->urlGenerator = $urlGenerator;
        $this->cacheDir = $kernel->getCacheDir();
    }

    /**
     * @param  PostalVotingRegistration  $postalVotingRegistration
     * @return string
     */
    public function generateSingleBallotPaper(PostalVotingRegistration $postalVotingRegistration): string
    {
        $dompdf = new Dompdf();

        $qrCode = QrCode::create($this->urlGenerator->generate('postal_voting_scan', [
            'id' => $postalVotingRegistration->getId()->toRfc4122()
        ], UrlGeneratorInterface::ABSOLUTE_URL));


        $writer = new PngWriter();
        $qrCodeResult = $writer->write($qrCode);

        if ($postalVotingRegistration->getLanguage() === 'en') {
            $template = 'PDF/BallotPaper/ballot_paper.en.html.twig';
        } else {
            $template = 'PDF/BallotPaper/ballot_paper.html.twig';
        }

        $html = $this->twig->render($template, [
            'registration' => $postalVotingRegistration,
            'qrCode' => $qrCodeResult->getDataUri(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->getOptions()->setIsRemoteEnabled(true);
        $dompdf->getOptions()->setFontCache($this->cacheDir);
        $dompdf->getOptions()->setTempDir($this->cacheDir);


        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();
        return $dompdf->output();
    }

    /**
     * @param  PostalVotingRegistration[]  $postalVotingRegistrations
     * @return string
     */
    public function generateMultipleBallotPapers(array $postalVotingRegistrations): string
    {
        $merger = new Merger();
        foreach($postalVotingRegistrations as $postalVotingRegistration) {
            $merger->addRaw($this->generateSingleBallotPaper($postalVotingRegistration));
        }

        return $merger->merge();
    }
}