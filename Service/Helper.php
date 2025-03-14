<?php

namespace Modules\ItkIssueCreate\Service;

use App\Thread;
use App\Conversation;
use Exception;
use Illuminate\Support\Facades\Log;
use Modules\CustomFields\Entities\ConversationCustomField;
use Modules\CustomFields\Entities\CustomField;

/**
 * Helper for freescout related stuff.
 */
readonly class Helper
{
  /**
   * Freescout note type.
   */
    private const NOTE_TYPE = 3;

  /**
   * Helper constructor for Freescout.
   *
   * @return void
   */
    public function __construct(private Thread $thread)
    {
    }

  /**
   * Add a Leantime reference to a Freescout ticket as a note.
   *
   * @param int $conversationId
   *   The id of the conversation to add the note to.
   * @param string|null $leantimeTicketUrl
   *   The URL of the Leantime ticket.
   *
   * @return void
   * @throws \Throwable
   */
    public function addLeantimeReference(int $conversationId, ?array $leantimeResult): void
    {
        $conversation = Conversation::all()->find($conversationId);

        $validLeantimeUrl = filter_var($leantimeResult['url'], FILTER_VALIDATE_URL);

        $this->thread->create(
            $conversation,
            self::NOTE_TYPE,
            $this->createHtmlDescription(
              $conversation->getOriginal(),
              $leantimeResult['url'],
              $validLeantimeUrl
            )
        );
        try {
          $customField = CustomField::where('name', '=', 'Leantime issue')->firstOrFail();
          $conversationCustomField = new ConversationCustomField();
          $conversationCustomField->setAttribute('conversation_id', $conversationId);
          $conversationCustomField->setAttribute('custom_field_id', $customField->getAttribute('id'));
          $conversationCustomField->setAttribute('value', $leantimeResult['id']);
          $conversationCustomField->save();
        }
        catch (Exception $exception) {
          Log::error(__FUNCTION__. ': ' . $exception->getMessage());
        }

    }

  /**
   * Add html description to Freescout note.
   *
   * @param array $conv
   *   The freescout conversation.
   * @param string|null $leantimeTicketUrl
   *   A URL to leantime ticket.
   * @param bool $validLeantimeUrl
   *   Whether the leantime url is valid.
   *
   * @return string
   *   Some rendered html for a Freescout note
   * @throws \Throwable
   */
    private function createHtmlDescription(array $conv, ?string $leantimeTicketUrl, bool $validLeantimeUrl): string
    {
        return view(
            'itkissuecreate::freescoutNote',
            compact(
                'conv',
                'leantimeTicketUrl',
                'validLeantimeUrl'
            )
        )->render();
    }
}
