<?php

namespace App\Controller;

use App\Entity\Item;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortableItemsRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SortableItemsController extends AbstractController
{
    #[Route('/sortable-items', name: 'app_item')]
    public function index(SortableItemsRepository $repo): Response
    {
        $items = $repo->findBy([], ['position' => 'asc']);

        return $this->render('sortableItems/index.html.twig', [
            'items' => $items
        ]);
    }

    /**
     * Reorder using Ajax (drag'n'drop)
     */
    #[Route('/reorder-items', name: 'update_item_pos')]
    public function updateItemPosition(Request $request, EntityManagerInterface $manager)
    {
        $item_id = $request->get('id');
        $position = $request->get('position');

        $item = $manager->getRepository(Item::class)->find($item_id);
        if (!$item) {
            return new JsonResponse(['error' => 'Item not found'], 404);
        }

        $item->setPosition($position);

        try {
            $manager->persist($item);
            $manager->flush();
            return new JsonResponse(true);
        } catch (\PDOException $e) {
            // Log the error to the console or error log
            error_log('PDOException occurred: ' . $e->getMessage());

            // Return a JSON response with an error message
            return new JsonResponse(['error' => 'An error occurred while updating item position'], 500);
        }
    }
}
