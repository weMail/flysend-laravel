<?php

namespace FlySend\Laravel;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class FlySendTransport extends AbstractTransport
{
    public function __construct(
        protected FlySendApiClient $client,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $envelope = $message->getEnvelope();

        $payload = [
            'from' => $envelope->getSender()->toString(),
            'to' => $this->getToAddress($email, $envelope),
            'subject' => $email->getSubject(),
        ];

        if ($html = $email->getHtmlBody()) {
            $payload['html'] = $html;
        }

        if ($text = $email->getTextBody()) {
            $payload['text'] = $text;
        }

        if ($cc = $email->getCc()) {
            $payload['cc'] = $this->stringifyAddresses($cc)[0] ?? null;
        }

        if ($bcc = $email->getBcc()) {
            $payload['bcc'] = $this->stringifyAddresses($bcc)[0] ?? null;
        }

        if ($replyTo = $email->getReplyTo()) {
            $payload['reply_to'] = $this->stringifyAddresses($replyTo)[0] ?? null;
        }

        $tags = $this->getTags($email);
        if ($tags) {
            $payload['tags'] = $tags;
        }

        $attachments = $this->getAttachments($email);
        if ($attachments) {
            $payload['attachments'] = $attachments;
        }

        // Remove null values
        $payload = array_filter($payload, fn ($value) => $value !== null);

        try {
            $result = $this->client->sendEmail($payload);

            $emailId = $result['data']['id'] ?? null;

            if ($emailId) {
                $email->getHeaders()->addHeader('X-FlySend-Email-ID', $emailId);
            }
        } catch (\Exception $e) {
            throw new TransportException(
                sprintf('Request to FlySend API failed. Reason: %s.', $e->getMessage()),
                is_int($e->getCode()) ? $e->getCode() : 0,
                $e
            );
        }
    }

    /**
     * Get the primary "to" address, excluding CC and BCC recipients.
     */
    protected function getToAddress(Email $email, Envelope $envelope): string
    {
        $recipients = $this->getRecipients($email, $envelope);

        return $this->stringifyAddresses($recipients)[0] ?? $envelope->getRecipients()[0]->toString();
    }

    /**
     * Get recipients without CC or BCC.
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter(
            $envelope->getRecipients(),
            fn (Address $address) => ! in_array($address, array_merge($email->getCc(), $email->getBcc()), true)
        );
    }

    /**
     * Extract tags from the X-FlySend-Tags header.
     */
    protected function getTags(Email $email): ?array
    {
        $header = $email->getHeaders()->get('X-FlySend-Tags');

        if (! $header) {
            return null;
        }

        $tags = json_decode($header->getBodyAsString(), true);

        // Remove the custom header so it's not sent to the recipient
        $email->getHeaders()->remove('X-FlySend-Tags');

        return is_array($tags) ? $tags : null;
    }

    /**
     * Convert Symfony email attachments to FlySend API format.
     */
    protected function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $attachments[] = [
                'filename' => $filename,
                'content' => base64_encode($attachment->getBody()),
                'mime_type' => $headers->get('Content-Type')->getBody(),
            ];
        }

        return $attachments;
    }

    public function __toString(): string
    {
        return 'flysend';
    }
}
