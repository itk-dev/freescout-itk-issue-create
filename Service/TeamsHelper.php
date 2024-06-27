<?php

namespace Modules\ItkIssueCreate\Service;

use Exception;
use GuzzleHttp\Client;
use App\Conversation;

/**
 * Helper for teams connection.
 */
class TeamsHelper
{

  /**
   * Use a Teams webhook to create a message for teams.
   *
   * @param Conversation $conversation
   *   The freescout conversation.
   * @param string $customerName
   *   The customers name.
   * @param string|null $leantimeTicketUrl
   *   THe URL to a leantime ticket.
   *
   * @return void
   */
    public function sendToTeams(Conversation $conversation, string $customerName, ?string $leantimeTicketUrl): void
    {
        $teamsWebHook = \config('itkissuecreate.teamsWebHook');

        if (empty($teamsWebHook)) {
            return;
        }

        $validLeantimeUrl = filter_var($leantimeTicketUrl, FILTER_VALIDATE_URL);

        $conv = $conversation->getOriginal();

        $client = new Client([
        'headers' => ['Content-Type' => 'application/json'],
        ]);

        try {
            $response = $client->post($teamsWebHook, [
            'timeout' => config('app.curl_timeout'),
            'connect_timeout' => config('app.curl_timeout'),
            'proxy' => config('app.proxy'),
            'body' => json_encode($this->getBody($conv, $customerName, $leantimeTicketUrl, $validLeantimeUrl))
            ]);
        } catch (Exception $e) {
            \Helper::logException($e);
        }
    }

  /**
   * Create the body for the Teams webhook post call.
   *
   * @param array $conv
   *   The original conversation.
   * @param string $customerName
   *   The customer name.
   * @param string $leantimeTicketUrl
   *   The URL to a Leantime ticket.
   * @param bool $validLeantimeUrl
   *   Whether the leantimeUrl is valid.
   *
   * @return array
   */
    private function getBody(
        array $conv,
        string $customerName,
        string $leantimeTicketUrl,
        bool $validLeantimeUrl
    ): array
    {

        $freescoutPath = config('app.url');

        if ($validLeantimeUrl) {
            $rightCol = [
            "type" => "ActionSet",
            "actions" => [
              [
                "type" => "Action.OpenUrl",
                "title" => "Open in Leantime",
                "url" => $leantimeTicketUrl
              ],
            ],
            ];
        } else {
            $rightCol = [
            "type" => "TextBlock",
            "text" => "Kunne ikke skabe Leantime URL",
            "wrap" => true,
            "spacing" => "None",
            ];
        }

        return [
        "type" => "message",
        "attachments" => [
        [
          "contentType" => "application/vnd.microsoft.card.adaptive",
          "content" => [
            "type" => "AdaptiveCard",
            "version" => "1.4",
            "body" => [
              [
                "type" => "TextBlock",
                "size" => "Medium",
                "weight" => "Bolder",
                "text" => $conv['subject']
              ],
              [
                "type" => "ColumnSet",
                "columns" => [
                  [
                    "type" => "Column",
                    "items" => [
                      [
                        "type" => "TextBlock",
                        "weight" => "Bolder",
                        "text" => $customerName,
                        "wrap" => true,
                        "spacing" => "None",
                        "size" => "Small"
                      ],
                      [
                        "type" => "TextBlock",
                        "weight" => "Bolder",
                        "text" => $conv['customer_email'],
                        "wrap" => true,
                        "spacing" => "None",
                        "size" => "Small"
                      ],
                      [
                        "type" => "TextBlock",
                        "spacing" => "None",
                        "text" => date("d-m-Y H:i", strtotime($conv['created_at'])),
                        "isSubtle" => true,
                        "wrap" => true,
                        "size" => "Small"
                      ],
                    ],
                    "width" => "stretch",
                  ],
                ],
              ],
              [
                "type" => "TextBlock",
                "text" => $conv['preview'],
                "wrap" => true,
              ],
              [
                "type" => "ColumnSet",
                "columns" => [
                  [
                    "type" => "Column",
                    "width" => "stretch",
                    "items" => [
                      [
                        "type" => "ActionSet",
                        "actions" => [
                          [
                            "type" => "Action.OpenUrl",
                            "title" => "Open in Freescout",
                            "url" => $freescoutPath . '/conversation/' . $conv['id']
                          ],
                        ],
                      ],
                    ],
                  ],
                  [
                    "type" => "Column",
                    "width" => "stretch",
                    "items" => [
                      $rightCol,
                    ],
                  ],
                ],
              ],
            ]
          ]
        ],
        ],
        ];
    }
}
