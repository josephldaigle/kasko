<?php

namespace Kasko\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CustomerReviewController extends AbstractController
{
    /**
     * Expose review submission form.
     *
     * @Route("/reviews", name="review_form")
     */
    public function index()
    {
        return $this->render('page/customer-review.html.twig', [
            'controller_name' => 'CustomerReviewController',
        ]);
    }
}
