<?php

namespace FlySend\Laravel\Tests;

use FlySend\Laravel\FlySendApiClient;
use FlySend\Laravel\FlySendException;
use FlySend\Laravel\FlySendTransport;
use Mockery;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FlySendTransportTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_transport_string_representation(): void
    {
        $client = Mockery::mock(FlySendApiClient::class);
        $transport = new FlySendTransport($client);

        $this->assertEquals('flysend', (string) $transport);
    }

    public function test_sends_basic_email(): void
    {
        $capturedPayload = null;

        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            })
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'test-email-id'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->subject('Test Subject')
            ->html('<p>Hello</p>')
            ->text('Hello');

        $transport->send($email);

        $this->assertEquals('sender@example.com', $capturedPayload['from']);
        $this->assertEquals('recipient@example.com', $capturedPayload['to']);
        $this->assertEquals('Test Subject', $capturedPayload['subject']);
        $this->assertEquals('<p>Hello</p>', $capturedPayload['html']);
        $this->assertEquals('Hello', $capturedPayload['text']);
        $this->assertArrayNotHasKey('cc', $capturedPayload);
        $this->assertArrayNotHasKey('bcc', $capturedPayload);
    }

    public function test_sends_email_with_cc_and_bcc(): void
    {
        $capturedPayload = null;

        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            })
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'test-email-id'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->cc(new Address('cc@example.com'))
            ->bcc(new Address('bcc@example.com'))
            ->subject('Test')
            ->text('Hello');

        $transport->send($email);

        $this->assertEquals('recipient@example.com', $capturedPayload['to']);
        $this->assertEquals('cc@example.com', $capturedPayload['cc']);
        $this->assertEquals('bcc@example.com', $capturedPayload['bcc']);
    }

    public function test_sends_email_with_reply_to(): void
    {
        $capturedPayload = null;

        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            })
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'test-email-id'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->replyTo(new Address('reply@example.com'))
            ->subject('Test')
            ->text('Hello');

        $transport->send($email);

        $this->assertEquals('reply@example.com', $capturedPayload['reply_to']);
    }

    public function test_sends_email_with_attachments(): void
    {
        $capturedPayload = null;

        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            })
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'test-email-id'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->subject('Test')
            ->text('Hello')
            ->attach('file content', 'test.txt', 'text/plain');

        $transport->send($email);

        $this->assertCount(1, $capturedPayload['attachments']);
        $this->assertEquals('test.txt', $capturedPayload['attachments'][0]['filename']);
        $this->assertEquals(base64_encode('file content'), $capturedPayload['attachments'][0]['content']);
    }

    public function test_sends_email_with_tags(): void
    {
        $tags = [['name' => 'campaign', 'value' => 'welcome']];
        $capturedPayload = null;

        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->withArgs(function (array $payload) use (&$capturedPayload) {
                $capturedPayload = $payload;

                return true;
            })
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'test-email-id'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->subject('Test')
            ->text('Hello');

        $email->getHeaders()->addTextHeader('X-FlySend-Tags', json_encode($tags));

        $transport->send($email);

        $this->assertEquals($tags, $capturedPayload['tags']);
    }

    public function test_throws_transport_exception_on_api_failure(): void
    {
        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->andThrow(new FlySendException('Invalid API key'));

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->subject('Test')
            ->text('Hello');

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Request to FlySend API failed. Reason: Invalid API key.');

        $transport->send($email);
    }

    public function test_sets_message_id_header_on_success(): void
    {
        $client = Mockery::mock(FlySendApiClient::class);
        $client->shouldReceive('sendEmail')
            ->once()
            ->andReturn([
                'success' => true,
                'data' => ['id' => 'flysend-msg-123'],
            ]);

        $transport = new FlySendTransport($client);

        $email = (new Email)
            ->from(new Address('sender@example.com'))
            ->to(new Address('recipient@example.com'))
            ->subject('Test')
            ->text('Hello');

        $sentMessage = $transport->send($email);

        $originalMessage = $sentMessage->getOriginalMessage();
        $header = $originalMessage->getHeaders()->get('X-FlySend-Email-ID');

        $this->assertNotNull($header);
        $this->assertEquals('flysend-msg-123', $header->getBodyAsString());
    }
}
