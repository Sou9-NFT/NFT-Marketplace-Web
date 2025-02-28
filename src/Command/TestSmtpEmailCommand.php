<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:test-smtp-email',
    description: 'Send a test email using SMTP configuration'
)]
class TestSmtpEmailCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure(): void
    {
        $this->addArgument('recipient', InputArgument::REQUIRED, 'Email address to send the test email to');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $recipient = $input->getArgument('recipient');

        try {
            $io->info('Preparing to send email...');
            
            $email = (new Email())
                ->from(new Address('linuxattack69@gmail.com', 'NFT Marketplace'))
                ->to($recipient)
                ->priority(Email::PRIORITY_HIGH)
                ->subject('NFT Marketplace - SMTP Test Email')
                ->text('This is a test email sent from the NFT Marketplace application.

This email was sent to verify SMTP configuration.
Time sent: ' . date('Y-m-d H:i:s') . '

If you received this email, the SMTP configuration is working correctly.')
                ->html('
                    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                        <h2 style="color: #333;">NFT Marketplace - SMTP Test</h2>
                        <p>This is a test email sent from the NFT Marketplace application.</p>
                        <p>This email was sent to verify SMTP configuration.</p>
                        <p><strong>Time sent:</strong> ' . date('Y-m-d H:i:s') . '</p>
                        <p style="color: #008000;">If you received this email, the SMTP configuration is working correctly.</p>
                    </div>
                ');

            $io->info('Attempting to send email...');
            $this->mailer->send($email);
            $io->info('Email has been accepted by SMTP server');

            $io->success([
                'Test email sent successfully to ' . $recipient,
                'Please check your inbox AND spam folder',
                'Time sent: ' . date('Y-m-d H:i:s')
            ]);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error([
                'Failed to send test email: ' . $e->getMessage(),
                'Error trace: ' . $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}