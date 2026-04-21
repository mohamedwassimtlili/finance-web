<?php
namespace App\Controller\Admin;

use App\Repository\BudgetRepository;
use App\Repository\ExpenseRepository;
use App\Repository\BillRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/finance', name: 'admin_finance_')]
class AdminFinanceController extends AbstractController
{
    #[Route('/budgets', name: 'budgets', methods: ['GET'])]
    public function budgets(BudgetRepository $repo): Response
    {
        return $this->render('admin/finance/budgets.html.twig', [
            'budgets' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/expenses', name: 'expenses', methods: ['GET'])]
    public function expenses(ExpenseRepository $repo): Response
    {
        return $this->render('admin/finance/expenses.html.twig', [
            'expenses' => $repo->findBy([], ['expenseDate' => 'DESC']),
        ]);
    }

    #[Route('/bills', name: 'bills', methods: ['GET'])]
    public function bills(BillRepository $repo): Response
    {
        return $this->render('admin/finance/bills.html.twig', [
            'bills' => $repo->findBy([], ['dueDay' => 'ASC']),
        ]);
    }
}
