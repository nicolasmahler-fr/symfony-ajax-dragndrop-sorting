<?php

namespace App\DataFixtures;

use App\Entity\Item;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for($i = 1; $i <= 10; $i++) {
            $item = new Item();
            $item->setName("item" . $i)
                ->setPosition($i);
            $manager->persist($item);
        }
        $manager->flush();
    }
}
