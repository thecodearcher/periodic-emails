# Keeping users engaged by sending out periodic emails in a Laravel application

In this tutorial, we will look at how we can send out periodic emails in a Laravel application using [Twilio SendGrid](https://sendgrid.com/).

## Prerequisites

In order to follow this tutorial you will need:

- Basic knowledge of Laravel
- [Laravel](https://laravel.com/docs/master) installed on your local machine
- [Composer](https://getcomposer.org/) globally installed
- [MySQL](https://www.mysql.com/downloads/) set up on your local machine
- [SendGrid Account](https://sendgrid.com/pricing/)

## Project setup

Start off by creating a new Laravel project for your application. This can be done either by using the [Laravel installer](https://laravel.com/docs/6.x#installing-laravel) or Composer. For this tutorial, the Laravel installer will be used. If you don't have the Laravel installer already installed, head over to the [Laravel documentation](https://laravel.com/docs/6.x#installing-laravel) to see how to. If you already do then open up a terminal and run the following command to create a new Laravel project:

    $ laravel new periodic-emails

Next, you need to install the [Sendgrid PHP Library](https://github.com/sendgrid/sendgrid-php) which will be used for communicating with the SendGrid service. Open up a terminal in your project directory and run the following command to get it installed via Composer:

    $ composer require "sendgrid/sendgrid"

After sucessful installation of the SendGrid library, head to your [SendGrid dashboard](https://app.sendgrid.com/settings/api_keys) to retrieve your API key which will be used to authenticate requests made with the library.

![https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_13.png](https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_13.png)

**NOTE:**  *If you don't have an API key yet, you can easily create one from the settings page.*

![https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_14.png](https://res.cloudinary.com/brianiyoha/image/upload/v1575608996/Articles%20sample/Group_14.png)

**NOTE:**  *Remember to keep a copy of your API key in a safe place as you won't be able to retrieve it later.*

Next, open up your `.env` file to add your API key to your environmental variables. Add the following at the end of the file:

    SENDGRID_API_KEY={YOUR API KEY}

## Setting up Database

The next step is to set up your database for the application. This tutorial will make use of the [MySQL](https://www.mysql.com/) database. If you don't already have it installed on your local machine, head over to their [official download page](https://www.mysql.com/downloads/) to have it downloaded and installed.

To create a database for your application, you will need to login to the MySQL client. To do this, simply run the following command:

    $ mysql -u {your_user_name}

**NOTE:** *Add the -p flag if you have a password for your MySQL instance.*

Run the following command to create a database:

    mysql> create database periodic-emails;
    mysql> exit;

Next, update your `.env` file with your database credentials. Open up `.env` and make the following adjustments:

    DB_DATABASE=food_ordering
    DB_USERNAME={username}
    DB_PASSWORD={password if any}

### Mocking Users Data

Since you will be sending out emails to your application users, you will need to already have their data stored in your database. For this tutorial, we will make use of the default scaffolded Users migration located in the `database/migrations` folder. 

Although this migration already exists, it isn't yet *committed* to your database. To commit the *users* migration, run the following command:

    $ php artisan migrate

This will create a `users` table in your database alongside the listed fields in the [up](https://laravel.com/docs/6.x/migrations#migration-structure) method of the migration file.

### Seeding the Users Table

As mentioned earlier, you will need some sample user(s) data to actually send out emails to. You can easily seed your database with *fake* data by using [seeders](https://laravel.com/docs/6.x/seeding). To generate a seeder class, open up a terminal in your project directory and run the following command:

    $ php artisan make:seeder UsersTableSeeder

This will generate a `UsersTableSeeder` seeder class in `database/seeds/`. Open the newly created file ( `database/seeds/UsersTableSeeder.php` ) and make the following changes:

    <?php
    
    use Illuminate\\Database\\Seeder;
    use Illuminate\\Support\\Facades\\DB;
    use Faker\\Generator as Faker;
    use Illuminate\\Support\\Facades\\Hash;
    
    class UsersTableSeeder extends Seeder
    {
        /**
         * Run the database seeds.
         *
         * @return void
         */
        public function run(Faker $faker)
        {
            DB::table("users")->insert([
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
                [
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail, //use real email here
                    'email_verified_at' => now(),
                    'password' => Hash::make($faker->password()), // password
                ],
            ]);
        }
    }

**NOTE:** *You have to swap out the email faker for real email addresses that you want to test your application with.*

Run the following command to seed your database with the data in the seeder class:

    $ php artisan db:seed --class=UsersTableSeeder

## Scheduling Mails

Now that you have some sample user data in your database, it's time to schedule emails to send at appointed times. In Laravel, there are several ways of [scheduling a task](https://laravel.com/docs/6.x/scheduling) and in this tutorial, we will make use of an [artisan](https://laravel.com/docs/6.x/artisan) command. 

First, create a new artisan command by running the following command in your terminal:

    $ php artisan make:command SendMails

This will generate a new Console command class in `app/Console/Commands/SendMails.php`, this file will house the needed logic for sending out emails via SendGrid. Now open the just created SendMails file and make the following changes:

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

As you would see, there are two properties and a method already present in the class namely `$signature` , `$description` and the `handle()` method. The `$signature` property is used to identify the *command* from the artisan console, so in this case to run this command you will have to do something like: 

    $ php artisan sendmails:send

while the `$description` property just like the name indicates is used to describe this command when the `artisan list` command is executed while the `handle()` method is called whenever the `sendmails:send` command is executed.

The `handle()` method has in this case, is used to send out emails to all registered users using the SendGrid SDK. First, the needed data for sending out emails are prepared using the helper classes in the SendGrid SDK. Next, a new `Mail` object is created using the `SendGrid\Mail\Mail` class which takes in five (5) arguments namely `from`, `to`, `subject`, `plainTextContent`, `htmlContent` .

     $email = new SendGrid\Mail\Mail(
                $from,
                null,
                $subject,
                null,
                $htmlContent
            );

You can see `null` is passed into the `to` parameter because this mail is meant to be sent to multiple users but also you don't want each user to know who else got the same mail. To ensure this mail is sent to each user individually, you have to make use of a `[personalization](https://sendgrid.com/docs/for-developers/sending-email/personalizations/)` object. A personalization object helps you create multiple options for each receiver of a mail.

     /* Get all registered users */
            $users = User::all();
            foreach ($users as $user) {
                /* Create new personalization object for each user and add to Mail object */
                $personalization = new Personalization();
                $personalization->addTo(new SendGrid\Mail\To(json_decode($user)->email, json_decode($user)->name));
                $email->addPersonalization($personalization);
            }

In this case, a  personalization object is created for each user and then added to the `Mail` object using the `addPersonalization` method available in the `Mail` class. Finally, The `$email`(Mail object) is then passed into the `send()` method from the SendGrid SDK which is used to send out the mails using the options set in the `Mail` object. 

### Scheduling The Command

At this point, you would have successfully created a custom artisan command to send out emails to your users. Next, let's actually schedule the mail. To do this, open up the `app/Console/Kernel.php` file and make the following changes:

    <?php
    
    namespace App\Console;
    
    use Illuminate\Console\Scheduling\Schedule;
    use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
    
    class Kernel extends ConsoleKernel
    {
        /**
         * The Artisan commands provided by your application.
         *
         * @var array
         */
        protected $commands = [
            //
        ];
    
        /**
         * Define the application's command schedule.
         *
         * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
         * @return void
         */
        protected function schedule(Schedule $schedule)
        {
            $schedule->command('sendmails:send')
                ->daily();
        }
    
        /**
         * Register the commands for the application.
         *
         * @return void
         */
        protected function commands()
        {
            $this->load(__DIR__ . '/Commands');
    
            require base_path('routes/console.php');
        }
    }

The custom command (`sendmails:send`)  has been added to the `schedule()` method and is set to fire daily using the `daily()` [frequency option](https://laravel.com/docs/5.8/scheduling#schedule-frequency-options). Next, you need to register a cron job to run the scheduler every minute which will, in turn, run your scheduled tasks in the `shedule()` method. If you know how to add a cron job on your server then go ahead and add the following

    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

 If not, then open up your terminal and run the following:

    $ crontab -e

This will open your server' crontab file, next add the following to the file:

    * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

## Testing

At this point, you should have successfully created a custom artisan command and also scheduled it to run daily. Testing the application can be broken down into:

- Testing the custom artisan command works - To do this simply open up a terminal in your project directory and run the following command:

        $ php artisan sendmails:send

- Testing the scheduled command works - To allow you test this command, you will have to adjust your schedule frequency to run the command every minute using the `everyMinute()` option. So replace the `daily()` frequency option with `everyMinute()` :

        /**
             * Define the application's command schedule.
             *
             * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
             * @return void
             */
            protected function schedule(Schedule $schedule)
            {
                $schedule->command('sendmails:send')
                    ->everyMinute();
            }

     Then proceed to run the following command in the terminal to run the scheduler:

         $ php artisan schedule:run

You should get a mail after testing either of the steps.

**NOTE:** *Remember to replace your schedule method with your desired frequency after testing the scheduled command.* 

## Conclusion

Now that you have finished this tutorial, you have learned how to send out emails using Twilio SendGrid in a Laravel application while also learning how to build and schedule a custom artisan command. If you will like to take a look at the complete source code for this tutorial, you can find it on [Github.](https://github.com/thecodearcher/periodic-emails)

I’d love to answer any question(s) you might have concerning this tutorial. You can reach me via

- Email: [brian.iyoha@gmail.com](mailto:brian.iyoha@gmail.com)
- Twitter: [thecodearcher](https://twitter.com/thecodearcher)
- GitHub: [thecodearcher](https://github.com/thecodearcher)
