<?php

namespace Modules\ItkIssueCreate\Service;

use Exception;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use App\Conversation;
use App\Thread;

/**
 * Helper for Leantime connection and creating Leantime ticket.
 */
final class LeantimeHelper
{

  /**
   * Leantime Api path.
   */
    private const API_PATH_JSONRPC = '/api/jsonrpc/';

  /**
   * Status for the ticket that is created.
   */
    private const LEANTIME_TICKET_STATUS = '3';

  /**
   * Path to the ticket (without the id)
   */
    private const LEANTIME_TICKET_PATH = '/tickets/showKanban#/tickets/showTicket/';

  /**
   * Use Leantime API to create the leantime ticket.
   *
   * @param Conversation $conversation
   * @param Thread $thread
   * @param String $customerName
   *
   * @return string|null
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Throwable
   */
    public function sendToLeantime(Conversation $conversation, Thread $thread, string $customerName): ?string
    {
        $conv = $conversation->getOriginal();

        $leantimeId = $this->addTicket([
        'headline' => $conv['subject'],
        'description' => $this->createHtmlDescription($conv, $customerName, $thread),
        'status' => self::LEANTIME_TICKET_STATUS,
        'projectId' => \config('itkissuecreate.leantimeProjectKey'),
        ]);

        if ($leantimeId) {
            return $this->createUrlFromId($leantimeId);
        }

        return null;
    }

  /**
   * Add ticket through a Leantime post call.
   *
   * @param array $values
   *
   * @return string
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
    public function addTicket(array $values): string
    {
        return $this->post('leantime.rpc.tickets.addTicket', [
        'values' => $values,
        ]);
    }

  /**
   * The post call using GuzzleHttp\Client.
   *
   * @param string $method
   *   The Leantime method to call.
   * @param array $params
   *   Required params for the method.
   *
   * @return string
   *   Id of the created Leantime ticket or an error response.
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
    private function post(string $method, array $params = []): string
    {
        $leantimeApiKey = \config('itkissuecreate.leantimeApiKey');

        $client = new Client([
        'headers' => ['Content-Type' => 'application/json'],
        ]);

        try {
            $response = $client->request(Request::METHOD_POST, $this->getLeantimeUrl() . self::API_PATH_JSONRPC, [
            'headers' => [
            'x-api-key' => $leantimeApiKey,
            ],
            'timeout' => config('app.curl_timeout'),
            'connect_timeout' => config('app.curl_timeout'),
            'proxy' => config('app.proxy'),
            'json' => [
            'jsonrpc' => '2.0',
            'method' => $method,
            'id' => uniqid(),
            'params' => $params,
            ],
            ]);
        } catch (Exception $e) {
            \Helper::logException($e);

            return $e->getResponse();
        }

        return json_decode($response->getBody()->getContents())->result[0];
    }

  /**
   * The Leantime URL as set in .env
   *
   * @return \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed
   */
    private function getLeantimeUrl()
    {
        return \config('itkissuecreate.leantimeUrl');
    }

  /**
   * Create a Leantime ticket URL.
   *
   * @param string $id
   *   Id of the Leantime ticket.
   *
   * @return string
   *   A full URL to the ticket in Leantime.
   */
    private function createUrlFromId(string $id) : string
    {
        return $this->getLeantimeUrl() . self::LEANTIME_TICKET_PATH . $id;
    }

  /**
   * Create HTML for Leantime ticket description.
   *
   * @param array $conv
   *   The Freescout conversation.
   * @param string $customerName
   *   The Customers name.
   * @param Thread $thread
   *   The Freescout Thread.
   *
   * @return string
   *   A rendered HTML description.
   * @throws \Throwable
   */
    private function createHtmlDescription(array $conv, string $customerName, Thread $thread): string
    {
        $freescoutPath = config('app.url');
        $freescoutUrl = $freescoutPath . '/conversation/' . $conv['id'];

        return view(
            'itkissuecreate::leantimeDescription',
            compact(
                'conv',
                'customerName',
                'thread',
                'freescoutUrl'
            )
        )->render();
    }
}
