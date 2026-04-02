<?php

namespace FlySend\Laravel;

class FlySend
{
    public function __construct(
        protected FlySendApiClient $client,
    ) {}

    /**
     * Send an email directly via the FlySend API.
     *
     * @param  array  $params  {from, to, subject, html, text, cc, bcc, reply_to, tags, attachments}
     * @return array
     *
     * @throws FlySendException
     */
    public function send(array $params): array
    {
        return $this->client->sendEmail($params);
    }
}
