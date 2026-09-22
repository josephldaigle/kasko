<?php

namespace Kasko\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kasko\Repository\FormLeadRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormLeadRepository::class)]
class FormLead
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    private $address;

    #[ORM\Column(type: 'string', length: 10)]
    private $phone_number;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 2000, maxMessage: 'The project description cannot be longer than {{ limit }} characters.')]
    private ?string $project_description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail( $email ): self
	{
		$this->email = $email;

		return $this;
	}

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): self
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getProjectDescription(): ?string
    {
        return $this->project_description;
    }

    public function setProjectDescription(?string $project_description): self
    {
        $this->project_description = $project_description;

        return $this;
    }
}
