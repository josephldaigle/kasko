<?php

namespace Kasko\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Kasko\Repository\CustomerReviewRepository")
 */
class CustomerReview
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

	/**
	 * @ORM\Column(type="integer")
     */
	private $review_score;

    /**
     * @ORM\Column(type="integer")
     */
	private $review_scale;

    /**
     * @ORM\Column(type="string", length=255)
     */
	private $reviewer_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $reviewer_location;

    /**
     * @ORM\Column(type="string", length=255)
     */
	private $review_text;

    /**
     * @ORM\Column(type="string", length=255)
     */
	private $source_name;

    /**
     * @ORM\Column(type="string", length=255)
     */
	private $source_url;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getReviewScore(): ?int
    {
        return $this->review_score;
    }

    public function setReviewScore($review_score): self
    {
        $this->review_score = $review_score;

        return $this;
    }

    public function getReviewScale(): ?int
    {
        return $this->review_scale;
    }

    public function setReviewScale($review_scale): self
    {
        $this->review_scale = $review_scale;

        return $this;
    }

    public function getReviewerName(): ?string
    {
        return $this->reviewer_name;
    }

    public function setReviewerName($reviewer_name): self
    {
        $this->reviewer_name = $reviewer_name;

        return $this;
    }

    public function getReviewerLocation(): ?string
    {
        return $this->reviewer_location;
    }

    public function setReviewerLocation($reviewer_location): self
    {
        $this->reviewer_location = $reviewer_location;

        return $this;
    }

    public function getReviewText(): ?string
    {
        return $this->review_text;
    }

    public function setReviewText($review_text): self
    {
        $this->review_text = $review_text;

        return $this;
    }

    public function getSourceName(): ?string
    {
        return $this->source_name;
    }

    public function setSourceName($source_name): self
    {
        $this->source_name = $source_name;

        return $this;
    }

    public function getSourceUrl(): ?string
    {
        return $this->source_url;
    }

    public function setSourceUrl($source_url): self
    {
        $this->source_url = $source_url;

        return $this;
    }

}