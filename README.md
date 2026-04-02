# FlySend Laravel

The official Laravel package for [FlySend](https://flysend.io) — send transactional emails through the FlySend API.

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12

## Installation

```bash
composer require flysend/flysend-laravel
```

## Configuration

Add your API key to `.env`:

```env
FLYSEND_API_KEY=your-api-key
```

Add the FlySend mailer to `config/mail.php`:

```php
'mailers' => [
    // ...

    'flysend' => [
        'transport' => 'flysend',
    ],
],
```

Set FlySend as your default mailer in `.env`:

```env
MAIL_MAILER=flysend
```

### Optional: Custom API Endpoint

If you're using a self-hosted FlySend instance:

```env
FLYSEND_API_ENDPOINT=https://your-flysend-instance.com
```

### Publishing Config

```bash
php artisan vendor:publish --tag=flysend-config
```

## Usage

### Option A: Laravel Mail (Mailables & Notifications)

Once configured as the default mailer, all existing Mailables and Notifications work automatically:

```php
use Illuminate\Support\Facades\Mail;

// Simple email
Mail::raw('Hello world!', function ($message) {
    $message->from('hello@example.com');
    $message->to('user@example.com');
    $message->subject('Hello');
});

// Mailable
Mail::to('user@example.com')->send(new WelcomeMail());
```

#### Adding Tags

You can attach FlySend tags to any Mailable by adding the `X-FlySend-Tags` header:

```php
class WelcomeMail extends Mailable
{
    public function build()
    {
        return $this->subject('Welcome!')
            ->html('<p>Welcome aboard!</p>')
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader(
                    'X-FlySend-Tags',
                    json_encode([
                        ['name' => 'campaign', 'value' => 'welcome'],
                    ])
                );
            });
    }
}
```

### Option B: FlySend Facade (Direct API)

For full control over the FlySend API, use the facade directly:

```php
use FlySend\Laravel\Facades\FlySend;

$response = FlySend::send([
    'from' => 'hello@example.com',
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'html' => '<p>Hello world!</p>',
    'text' => 'Hello world!',
    'reply_to' => 'support@example.com',
    'tags' => [
        ['name' => 'campaign', 'value' => 'welcome'],
        ['name' => 'user_id', 'value' => '12345'],
    ],
    'attachments' => [
        [
            'filename' => 'invoice.pdf',
            'content' => base64_encode($pdfContent),
            'mime_type' => 'application/pdf',
        ],
    ],
]);

// $response['data']['id'] contains the email ID
```

## License

MIT
