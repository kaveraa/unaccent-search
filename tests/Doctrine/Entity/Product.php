<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Doctrine\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column(nullable: true)]
        public ?string $name = null,
        #[ORM\Column(nullable: true)]
        public ?string $code = null,
    ) {
    }
}
