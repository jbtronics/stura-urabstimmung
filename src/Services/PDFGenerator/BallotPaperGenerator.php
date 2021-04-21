<?php


namespace App\Services\PDFGenerator;


use App\Entity\PostalVotingRegistration;
use Dompdf\Dompdf;
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

        $helpQrCode = QrCode::create('CHANGEME');
        $helpQrCodeResult = $writer->write($helpQrCode);

        $html = $this->twig->render('PDF/BallotPaper/ballot_paper.html.twig', [
            'registration' => $postalVotingRegistration,
            'qrCode' => $qrCodeResult->getDataUri(),
            'help_qrCode' => $helpQrCodeResult->getDataUri(),
        ]);

        $dompdf->loadHtml($html);
        $dompdf->getOptions()->setIsRemoteEnabled(true);
        //Use cache proper cache dir
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