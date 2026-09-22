<?php

namespace Kasko\Entity;

use Doctrine\ORM\Mapping as ORM;
use Kasko\Repository\TenantRepository;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
class Tenant implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private $uuid;

    #[ORM\Column(type: 'json')]
    private $devices = [];

    #[ORM\Column(type: 'json')]
    private $roles = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getDevices()
    {
        return $this->devices;
    }

    public function setDevices($devices)
    {
        $this->devices = $devices;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->uuid;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
