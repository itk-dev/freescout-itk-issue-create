<?php

namespace Modules\ItkIssueCreate\Service;

use App\Thread;
use App\Conversation;

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
   * @param string $leantimeTicketUrl
   *   The URL of the Leantime ticket.
   *
   * @return void
   * @throws \Throwable
   */
    public function addLeantimeReference(int $conversationId, string $leantimeTicketUrl): void
    {
        $conversation = Conversation::all()->find($conversationId);

        $validLeantimeUrl = filter_var($leantimeTicketUrl, FILTER_VALIDATE_URL);

        $this->thread->create(
            $conversation,
            self::NOTE_TYPE,
            $this->createHtmlDescription(
                $conversation->getOriginal(),
                $leantimeTicketUrl
            ),
            $validLeantimeUrl
        );
    }

  /**
   * Add html description to Freescout note.
   *
   * @param array $conv
   *   The freescout conversation.
   * @param string $leantimeTicketUrl
   *   A URL to leantime ticket.
   * @param bool $validLeantimeUrl
   *   Whether the leantime url is valid.
   *
   * @return string
   *   Some rendered html for a Freescout note
   * @throws \Throwable
   */
    private function createHtmlDescription(array $conv, string $leantimeTicketUrl, bool $validLeantimeUrl): string
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
