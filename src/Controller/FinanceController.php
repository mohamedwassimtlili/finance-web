<?php
namespace App\Controller;

use App\Entity\Budget;
use App\Entity\Expense;
use App\Entity\Bill;
use App\Repository\BudgetRepository;
use App\Repository\ExpenseRepository;
use App\Repository\BillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/finance', name: 'finance_')]
class FinanceController extends AbstractController
{
    // ─── BUDGETS ──────────────────────────────────────────────────────────────

    #[Route('/budgets', name: 'budgets', methods: ['GET'])]
    public function budgets(BudgetRepository $repo): Response
    {
        return $this->render('finance/budgets/index.html.twig', [
            'budgets' => $repo->findBy(['user' => $this->getUser()], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/budgets/new', name: 'budget_new', methods: ['GET', 'POST'])]
    public function newBudget(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $budget = new Budget();
            $budget->setUser($this->getUser());
            $budget->setName($request->request->get('name'));
            $budget->setAmount($request->request->get('amount'));
            $budget->setStartDate(new \DateTime($request->request->get('start_date')));
            $budget->setEndDate(new \DateTime($request->request->get('end_date')));
            $budget->setCategory($request->request->get('category') ?: null);

            $em->persist($budget);
            $em->flush();

            $this->addFlash('success', 'Budget created successfully.');
            return $this->redirectToRoute('finance_budgets');
        }

        return $this->render('finance/budgets/new.html.twig');
    }

    #[Route('/budgets/{id}', name: 'budget_show', methods: ['GET'])]
    public function showBudget(Budget $budget): Response
    {
        if ($budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $totalExpenses = array_sum(array_map(fn($e) => (float)$e->getAmount(), $budget->getExpenses()->toArray()));
        $totalBills    = array_sum(array_map(fn($b) => (float)$b->getAmount(), $budget->getBills()->toArray()));

        return $this->render('finance/budgets/show.html.twig', [
            'budget'        => $budget,
            'totalExpenses' => $totalExpenses,
            'totalBills'    => $totalBills,
        ]);
    }

    #[Route('/budgets/{id}/edit', name: 'budget_edit', methods: ['GET', 'POST'])]
    public function editBudget(Budget $budget, Request $request, EntityManagerInterface $em): Response
    {
        if ($budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $budget->setName($request->request->get('name'));
            $budget->setAmount($request->request->get('amount'));
            $budget->setStartDate(new \DateTime($request->request->get('start_date')));
            $budget->setEndDate(new \DateTime($request->request->get('end_date')));
            $budget->setCategory($request->request->get('category') ?: null);

            $em->flush();

            $this->addFlash('success', 'Budget updated.');
            return $this->redirectToRoute('finance_budget_show', ['id' => $budget->getId()]);
        }

        return $this->render('finance/budgets/edit.html.twig', ['budget' => $budget]);
    }

    #[Route('/budgets/{id}/delete', name: 'budget_delete', methods: ['POST'])]
    public function deleteBudget(Budget $budget, Request $request, EntityManagerInterface $em): Response
    {
        if ($budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_budget_' . $budget->getId(), $request->request->get('_token'))) {
            $em->remove($budget);
            $em->flush();
            $this->addFlash('success', 'Budget deleted.');
        }

        return $this->redirectToRoute('finance_budgets');
    }

    // ─── EXPENSES ─────────────────────────────────────────────────────────────

    #[Route('/expenses', name: 'expenses', methods: ['GET'])]
    public function expenses(BudgetRepository $budgetRepo): Response
    {
        $budgets = $budgetRepo->findBy(['user' => $this->getUser()]);
        $expenses = [];
        foreach ($budgets as $b) {
            foreach ($b->getExpenses() as $e) {
                $expenses[] = $e;
            }
        }
        usort($expenses, fn($a, $b) => $b->getExpenseDate() <=> $a->getExpenseDate());

        return $this->render('finance/expenses/index.html.twig', ['expenses' => $expenses]);
    }

    #[Route('/budgets/{id}/expenses/new', name: 'expense_new', methods: ['GET', 'POST'])]
    public function newExpense(Budget $budget, Request $request, EntityManagerInterface $em): Response
    {
        if ($budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $expense = new Expense();
            $expense->setBudget($budget);
            $expense->setAmount($request->request->get('amount'));
            $expense->setCategory($request->request->get('category') ?: null);
            $expense->setExpenseDate(new \DateTime($request->request->get('expense_date')));
            $expense->setDescription($request->request->get('description') ?: null);

            // update spentAmount on budget
            $budget->setSpentAmount((string)((float)$budget->getSpentAmount() + (float)$expense->getAmount()));

            $em->persist($expense);
            $em->flush();

            $this->addFlash('success', 'Expense added.');
            return $this->redirectToRoute('finance_budget_show', ['id' => $budget->getId()]);
        }

        return $this->render('finance/expenses/new.html.twig', ['budget' => $budget]);
    }

    #[Route('/expenses/{id}/edit', name: 'expense_edit', methods: ['GET', 'POST'])]
    public function editExpense(Expense $expense, Request $request, EntityManagerInterface $em): Response
    {
        if ($expense->getBudget()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $oldAmount = (float)$expense->getAmount();
            $expense->setAmount($request->request->get('amount'));
            $expense->setCategory($request->request->get('category') ?: null);
            $expense->setExpenseDate(new \DateTime($request->request->get('expense_date')));
            $expense->setDescription($request->request->get('description') ?: null);

            // adjust spentAmount
            if ($expense->getBudget()) {
                $diff = (float)$expense->getAmount() - $oldAmount;
                $expense->getBudget()->setSpentAmount((string)((float)$expense->getBudget()->getSpentAmount() + $diff));
            }

            $em->flush();
            $this->addFlash('success', 'Expense updated.');

            $budgetId = $expense->getBudget()?->getId();
            return $budgetId
                ? $this->redirectToRoute('finance_budget_show', ['id' => $budgetId])
                : $this->redirectToRoute('finance_expenses');
        }

        return $this->render('finance/expenses/edit.html.twig', ['expense' => $expense]);
    }

    #[Route('/expenses/{id}/delete', name: 'expense_delete', methods: ['POST'])]
    public function deleteExpense(Expense $expense, Request $request, EntityManagerInterface $em): Response
    {
        if ($expense->getBudget()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_expense_' . $expense->getId(), $request->request->get('_token'))) {
            if ($expense->getBudget()) {
                $expense->getBudget()->setSpentAmount(
                    (string)max(0, (float)$expense->getBudget()->getSpentAmount() - (float)$expense->getAmount())
                );
            }
            $em->remove($expense);
            $em->flush();
            $this->addFlash('success', 'Expense deleted.');
        }

        $budgetId = $expense->getBudget()?->getId();
        return $budgetId
            ? $this->redirectToRoute('finance_budget_show', ['id' => $budgetId])
            : $this->redirectToRoute('finance_expenses');
    }

    // ─── BILLS ────────────────────────────────────────────────────────────────

    #[Route('/bills', name: 'bills', methods: ['GET'])]
    public function bills(BudgetRepository $budgetRepo): Response
    {
        $budgets = $budgetRepo->findBy(['user' => $this->getUser()]);
        $bills = [];
        foreach ($budgets as $b) {
            foreach ($b->getBills() as $bill) {
                $bills[] = $bill;
            }
        }
        usort($bills, fn($a, $b) => $a->getDueDay() <=> $b->getDueDay());

        return $this->render('finance/bills/index.html.twig', ['bills' => $bills]);
    }

    #[Route('/budgets/{id}/bills/new', name: 'bill_new', methods: ['GET', 'POST'])]
    public function newBill(Budget $budget, Request $request, EntityManagerInterface $em): Response
    {
        if ($budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $bill = new Bill();
            $bill->setBudget($budget);
            $bill->setName($request->request->get('name'));
            $bill->setAmount($request->request->get('amount'));
            $bill->setDueDay((int)$request->request->get('due_day'));
            $bill->setFrequency($request->request->get('frequency'));
            $bill->setCategory($request->request->get('category') ?: null);
            $bill->setDescription($request->request->get('description') ?: null);
            $bill->setStatus('UNPAID');

            $em->persist($bill);
            $em->flush();

            $this->addFlash('success', 'Bill added.');
            return $this->redirectToRoute('finance_budget_show', ['id' => $budget->getId()]);
        }

        return $this->render('finance/bills/new.html.twig', ['budget' => $budget]);
    }

    #[Route('/bills/{id}/edit', name: 'bill_edit', methods: ['GET', 'POST'])]
    public function editBill(Bill $bill, Request $request, EntityManagerInterface $em): Response
    {
        if ($bill->getBudget()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $bill->setName($request->request->get('name'));
            $bill->setAmount($request->request->get('amount'));
            $bill->setDueDay((int)$request->request->get('due_day'));
            $bill->setFrequency($request->request->get('frequency'));
            $bill->setCategory($request->request->get('category') ?: null);
            $bill->setDescription($request->request->get('description') ?: null);

            $em->flush();
            $this->addFlash('success', 'Bill updated.');

            $budgetId = $bill->getBudget()?->getId();
            return $budgetId
                ? $this->redirectToRoute('finance_budget_show', ['id' => $budgetId])
                : $this->redirectToRoute('finance_bills');
        }

        return $this->render('finance/bills/edit.html.twig', ['bill' => $bill]);
    }

    #[Route('/bills/{id}/pay', name: 'bill_pay', methods: ['POST'])]
    public function payBill(Bill $bill, Request $request, EntityManagerInterface $em): Response
    {
        if ($bill->getBudget()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('pay_bill_' . $bill->getId(), $request->request->get('_token'))) {
            $bill->setStatus('PAID');
            $em->flush();
            $this->addFlash('success', 'Bill marked as paid.');
        }

        $budgetId = $bill->getBudget()?->getId();
        return $budgetId
            ? $this->redirectToRoute('finance_budget_show', ['id' => $budgetId])
            : $this->redirectToRoute('finance_bills');
    }

    #[Route('/bills/{id}/delete', name: 'bill_delete', methods: ['POST'])]
    public function deleteBill(Bill $bill, Request $request, EntityManagerInterface $em): Response
    {
        if ($bill->getBudget()?->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete_bill_' . $bill->getId(), $request->request->get('_token'))) {
            $em->remove($bill);
            $em->flush();
            $this->addFlash('success', 'Bill deleted.');
        }

        $budgetId = $bill->getBudget()?->getId();
        return $budgetId
            ? $this->redirectToRoute('finance_budget_show', ['id' => $budgetId])
            : $this->redirectToRoute('finance_bills');
    }
}
