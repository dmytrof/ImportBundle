<?php

namespace Dmytrof\ImportBundle\Tests\Functional\App\Entity;

use Dmytrof\ImportBundle\Tests\Functional\App\Repository\ProductRepository;
use Dmytrof\ModelsManagementBundle\Model\{SimpleModelInterface, Traits\SimpleModelTrait};
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'test_product')]
class Product implements SimpleModelInterface
{
    use SimpleModelTrait;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    protected $id;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank]
    protected ?string $title = null;

    #[ORM\Column(type: 'float', nullable: true)]
    protected ?float $price = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }
}
