<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use SendGrid;
use SendGrid\Mail\From;
use SendGrid\Mail\HtmlContent;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Personalization;
use SendGrid\Mail\Subject;

class SendMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sendmails:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends mails to users';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $from = new SendGrid\Mail\From(env('MAIL_FROM_ADDRESS'));

        /* Set the subject of mail */
        $subject = new SendGrid\Mail\Subject("Daily Mail Update!");

        /* Set mail body */
        $htmlContent = new SendGrid\Mail\HtmlContent("This is a mail from " . getenv('APP_NAME') . " sent at " . now());

        $email = new SendGrid\Mail\Mail(
            $from,
            null,
            $subject,
            null,
            $htmlContent
        );

        /* Get all registered users */
        $users = User::all();
        foreach ($users as $user) {
            /* Create new personalization object for each user and add to Mail object */
            $personalization = new Personalization();
            $personalization->addTo(new SendGrid\Mail\To(json_decode($user)->email, json_decode($user)->name));
            $email->addPersonalization($personalization);
        }

        /* Create instance of Sendgrid SDK */
        $sendgrid = new SendGrid(getenv('SENDGRID_API_KEY'));
        /* Send mail using sendgrid instance */
        $sendgrid->send($email);
    }
}
