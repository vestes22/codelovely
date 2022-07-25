<?php

namespace GoDaddy\WordPress\MWC\Common\DataSources\WordPress\Adapters;

use Exception;
use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Models\Review;
use GoDaddy\WordPress\MWC\Common\Models\User;
use WP_Comment;

/**
 * Review adapter.
 *
 * Converts between a native review object and a WordPress comment object.
 */
class ReviewAdapter implements DataSourceAdapterContract
{
    /** @var WP_Comment WordPress comment object */
    protected $source;

    /** @var string the review class name */
    protected $reviewClass = Review::class;

    /**
     * Review adapter constructor.
     *
     * @param WP_Comment $comment WordPress comment object
     */
    public function __construct(WP_Comment $comment)
    {
        $this->source = $comment;
    }

    /**
     * Converts a WordPress comment object into a native review object.
     *
     * @return Review
     * @throws Exception
     */
    public function convertFromSource()
    {
        if (! isset($this->source->comment_type) || 'review' !== $this->source->comment_type) {
            return null;
        }

        return (new $this->reviewClass())
            ->setAuthor($this->extractUserFromWpComment($this->source))
            ->setContent($this->source->comment_content ?? '')
            ->setDateGmt($this->source->comment_date_gmt ?? null)
            ->setProductId($this->source->comment_post_ID ?? null)
            ->setStatus($this->convertStatus($this->source->comment_approved ?? null));
    }

    /**
     * Converts a native review object into a WordPress comment object.
     *
     * @param Review|null $review native review object to convert
     * @return WP_Comment WordPress review object
     * @throws Exception
     */
    public function convertToSource(Review $review = null)
    {
        if (! $review instanceof Review) {
            return $this->source;
        }

        if ($user = $review->getAuthor()) {
            $this->source->user_id = $user->getId();
            $this->source->comment_author = $user->getDisplayName();
            $this->source->comment_author_email = $user->getEmail();
        }

        $this->source->comment_content = $review->getContent();
        $this->source->comment_date_gmt = $review->getDateGmt();
        $this->source->comment_post_ID = $review->getProductId();
        $this->source->comment_approved = $review->getStatus();

        return $this->source;
    }

    /**
     * Extracts a user from a WordPress comment object.
     *
     * @param WP_Comment $comment
     * @return User
     */
    protected function extractUserFromWpComment(WP_Comment $comment) : User
    {
        return (new User())
            ->setId($comment->user_id ?? 0)
            ->setDisplayName($comment->comment_author ?? '')
            ->setEmail($comment->comment_author_email ?? '');
    }

    /**
     * Converts a status from and to source.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_comment/
     *
     * @param int|string|null $commentApproved
     *
     * @throws Exception
     */
    protected function convertStatus($commentApproved) : string
    {
        // list of possible statuses from WP_Comment docs
        $statusMap = [
            '0' => 'pending',
            '1' => 'approved',
        ];

        // combines the status map with its flipped version to allow converting back and forth
        $statusMap = ArrayHelper::combine($statusMap, array_flip($statusMap));

        return strval($statusMap[$commentApproved] ?? $commentApproved);
    }
}
