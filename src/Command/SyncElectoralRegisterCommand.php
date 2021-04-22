<?php

namespace App\Command;

use App\Entity\PostalVotingRegistration;
use App\Repository\PostalVotingRegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
use League\Csv\Writer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SyncElectoralRegisterCommand extends Command
{
    protected static $defaultName = 'app:sync-electoral-register';
    protected static $defaultDescription = 'Synchronize postal voting databse with the voting register';

    private $entityManager;

    public function __construct(string $name = null, EntityManagerInterface $entityManager)
    {
        parent::__construct($name);

        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('input', InputArgument::REQUIRED, 'The input electoral register as CSV')
            ->addArgument('output', InputArgument::OPTIONAL, 'The output electoral registrer as CSV')
            ->addOption('dry', null, InputOption::VALUE_NONE, 'Dry run (Dont write changes to databse)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filename = $input->getArgument('input');
        $dry = $input->getOption('dry');

        $csv = Reader::createFromPath($filename, 'r');
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $output_filename = $input->getArgument('output');
        if (!empty($output_filename)) {
            $writer = Writer::createFromPath($output_filename, 'w');
            $writer->setDelimiter(';');
            //Insert header
            $writer->insertOne(['Matrikel', 'Nachname', 'Vorname', 'Email', 'Wahlschein beantragt', 'Wahlschein gedruckt', 'Wahlschein eingegangen']);
        }

        $io->info('Use file ' . $csv->getPathname());
        $io->info(sprintf('File contains %d entries', $csv->count()));

        /** @var PostalVotingRegistrationRepository $repo */
        $repo = $this->entityManager->getRepository(PostalVotingRegistration::class);

        $output_csv = [];

        foreach ($csv as $entry)
        {
            $io->note(sprintf('Check for %s (%s; %s)', $entry['Vorname'] . ' ' . $entry['Nachname'], $entry['Email'], $entry['Matrikel']));
            //Try to find an registration for this entry
            $registration = $repo->findByMail($entry['Email']);
            if ($registration !== null) {
                $io->note(sprintf('Found registration: %s', $registration->getId()->toRfc4122()));
                $this->correctStudentNumber($io, $registration, $entry);
                $this->verifyRegistration($io, $registration, $entry);

                $entry = $this->updateVotingRegisterLine($registration, $entry);
            }
            if (isset($writer)) {
                $writer->insertOne($entry);
            }
        }

        if (!$dry) {
            $this->entityManager->flush();
            $io->success('Successfully wrote changes to database!');
        } else {
            $io->warning('Dry run mode activated. Changes were not written to DB.');
        }

        return Command::SUCCESS;
    }

    private function updateVotingRegisterLine(PostalVotingRegistration $registration, array $line): array
    {
        //We start with empty data so that entries can be removed from list too...
        $line['Wahlschein gedruckt'] = '';
        $line['Wahlschein beantragt'] = '';
        $line['Wahlschein eingegangen'] = '';

        if ($registration->isUnwarranted()) {
            return $line;
        }

        if ($registration->isConfirmed()) {
            $line['Wahlschein beantragt'] = $registration->getCreationDate()->format('d.m.Y H:i:s');
        }
        if ($registration->isPrinted()) {
            $line['Wahlschein gedruckt'] = 'Ja';
        }
        if ($registration->isCounted()) {
            $line['Wahlschein eingegangen'] = 'Ja';
        }

        return $line;
    }


    private function correctStudentNumber(SymfonyStyle $io, PostalVotingRegistration $registration, array $line)
    {
        $real_student_number = $line['Matrikel'];

        if ($registration->getStudentNumber() !== $real_student_number) {
            $io->warning(sprintf('Submitted student number (%s) does not match real student number (%s). Correcting...', $registration->getStudentNumber(), $real_student_number));
            $registration->setStudentNumber($real_student_number);
        }
    }

    private function verifyRegistration(SymfonyStyle $io, PostalVotingRegistration $registration, array $line)
    {
        if (!$registration->isConfirmed()) {
            $io->note('Registration is not confirmed yet. Skip for now...');
            return;
        }
        if ($registration->isUnwarranted()) {
            $io->note('Registration is unwarranted. Skip');
            return;
        }
        if ($registration->isVerified()) {
            return;
        }

        //Just a little check to prevent wrong verification
        if ($registration->getEmail() !== $line['Email']) {
            throw new \InvalidArgumentException(sprintf('Registration and line must match! (%s  !== %s)', $registration->getEmail(), $line['Email']));
        }

        $io->note('Mark registration as verified');
        $registration->setVerified(true);
    }

}
