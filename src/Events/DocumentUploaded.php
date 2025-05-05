<?php

namespace RagKit\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RagKit\Models\RagDocument;

class DocumentUploaded
{
    use Dispatchable, SerializesModels;

    /**
     * The uploaded document instance.
     *
     * @var \RagKit\Models\RagDocument
     */
    public $document;

    /**
     * Create a new event instance.
     *
     * @param  \RagKit\Models\RagDocument  $document
     * @return void
     */
    public function __construct(RagDocument $document)
    {
        $this->document = $document;
    }
} 