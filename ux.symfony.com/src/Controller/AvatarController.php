<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class AvatarController extends AbstractController
{
    #[Route('/avatar')]
    public function index()
    {
        return $this->render('avatar.html.twig');
    }
}