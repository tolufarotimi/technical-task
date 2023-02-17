<?php

// src/Controller/AdminController.php

namespace App\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Controller\EasyAdminController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends EasyAdminController
{
    public function deleteNewsAction(): Response
    {
        $id = $this->request->query->get('id');

        // get the news by id
        $news = $this->getDoctrine()->getRepository(News::class)->find($id);

        if (!$news) {
            throw $this->createNotFoundException('News not found');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($news);
        $entityManager->flush();

        $this->addFlash('success', 'News deleted successfully');

        return $this->redirectToRoute('easyadmin');
    }
}
