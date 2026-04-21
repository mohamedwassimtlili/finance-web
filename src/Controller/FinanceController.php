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
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/finance', name: 'finance_')]
class FinanceController extends AbstractController
{
    // ─── BUDGETS ──────────────────────────────────────────────────────────────
#[Route('/budgets', name: 'budgets', methods: ['GET'])]
public function budgets(BudgetRepository $repo): Response
{
    $user    = $this->getUser();
    $budgets = $repo->findBy(['user' => $user], ['createdAt' => 'DESC']);

    // ── Financial Health Score ────────────────────────────
    $scores = [];
    foreach ($budgets as $budget) {
        if ((float)$budget->getAmount() > 0) {
            $pct = ((float)$budget->getSpentAmount() / (float)$budget->getAmount()) * 100;
            if ($pct <= 50)      $scores[] = 100;
            elseif ($pct <= 75)  $scores[] = 80;
            elseif ($pct <= 90)  $scores[] = 60;
            elseif ($pct <= 100) $scores[] = 40;
            else                 $scores[] = 10;
        }
    }
    $healthScore = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
    if ($healthScore >= 80)     $healthStatus = 'Excellent';
    elseif ($healthScore >= 60) $healthStatus = 'Good';
    elseif ($healthScore >= 40) $healthStatus = 'Fair';
    else                        $healthStatus = 'Poor';

    // ── Spending Patterns ─────────────────────────────────
    $categoryTotals = [];
    $dayTotals      = [];
    $allExpenses    = [];
    foreach ($budgets as $budget) {
        foreach ($budget->getExpenses() as $expense) {
            $allExpenses[] = $expense;
            $cat = $expense->getCategory() ?? 'Uncategorized';
            $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + (float)$expense->getAmount();
            $day = $expense->getExpenseDate()->format('l');
            $dayTotals[$day] = ($dayTotals[$day] ?? 0) + (float)$expense->getAmount();
        }
    }
    arsort($categoryTotals);
    arsort($dayTotals);
    $biggestCategory  = !empty($categoryTotals) ? array_key_first($categoryTotals) : 'N/A';
    $mostExpensiveDay = !empty($dayTotals)       ? array_key_first($dayTotals)      : 'N/A';
    $avgExpense = count($allExpenses) > 0
        ? array_sum(array_map(fn($e) => (float)$e->getAmount(), $allExpenses)) / count($allExpenses)
        : 0;

    // ── Smart Notifications ───────────────────────────────
    $notifications = [];
    foreach ($budgets as $budget) {
        $pct = (float)$budget->getAmount() > 0
            ? ((float)$budget->getSpentAmount() / (float)$budget->getAmount()) * 100
            : 0;
        if ($pct > 100) {
            $notifications[] = ['type' => 'danger',  'message' => '⚠️ Budget "' . $budget->getName() . '" is overspent!'];
        } elseif ($pct >= 80) {
            $notifications[] = ['type' => 'warning', 'message' => '🔔 Budget "' . $budget->getName() . '" is near limit (' . round($pct) . '% used)'];
        }
    }
    if (count($allExpenses) === 0) {
        $notifications[] = ['type' => 'info', 'message' => '📝 No expenses yet. Start tracking your spending!'];
    }

    return $this->render('budget/index.html.twig', [
        'budgets'          => $budgets,
        'healthScore'      => round($healthScore),
        'healthStatus'     => $healthStatus,
        'biggestCategory'  => $biggestCategory,
        'mostExpensiveDay' => $mostExpensiveDay,
        'avgExpense'       => round($avgExpense, 2),
        'notifications'    => $notifications,
        'categoryTotals'   => $categoryTotals,
        'dayTotals'        => $dayTotals,
        'forecast'         => $this->calculateForecast($budgets),
    ]);
}

    /**
     * Calculate predictive spending forecast for the current month
     */
    private function calculateForecast(array $budgets): array
    {
        $currentMonth = (int)date('m');
        $currentYear = (int)date('Y');
        $daysInMonth = (int)date('t');
        $currentDay = (int)date('j');
        
        $monthExpenses = 0;
        
        foreach ($budgets as $budget) {
            foreach ($budget->getExpenses() as $expense) {
                $date = $expense->getExpenseDate();
                if ((int)$date->format('m') === $currentMonth && (int)$date->format('Y') === $currentYear) {
                    $monthExpenses += (float)$expense->getAmount();
                }
            }
        }
        
        if ($currentDay === 1 && $monthExpenses == 0) {
            $dailyRate = 0;
        } else {
            $dailyRate = $monthExpenses / $currentDay;
        }
        
        $projectedTotal = $dailyRate * $daysInMonth;
        
        return [
            'current_month_spent' => $monthExpenses,
            'daily_rate' => $dailyRate,
            'projected_total' => $projectedTotal,
            'days_remaining' => $daysInMonth - $currentDay
        ];
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

    #[Route('/budgets/export/pdf', name: 'export_pdf')]
    public function exportPdf(BudgetRepository $budgetRepo): Response
    {
        $user = $this->getUser();
        $budgets = $budgetRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        $totalBudget = 0;
        $totalSpent = 0;
        $expenses = [];
        $scores = [];
        $highestExpense = 0;
        $categoryTotals = [];
        
        foreach ($budgets as $budget) {
            $amt = (float)$budget->getAmount();
            $spent = (float)$budget->getSpentAmount();
            $totalBudget += $amt;
            $totalSpent += $spent;
            
            if ($amt > 0) {
                $pct = ($spent / $amt) * 100;
                if ($pct <= 50) $scores[] = 100;
                elseif ($pct <= 75) $scores[] = 80;
                elseif ($pct <= 90) $scores[] = 60;
                elseif ($pct <= 100) $scores[] = 40;
                else $scores[] = 10;
            }
            
            foreach ($budget->getExpenses() as $expense) {
                $expenses[] = $expense;
                $eAmt = (float)$expense->getAmount();
                if ($eAmt > $highestExpense) $highestExpense = $eAmt;
                $cat = $expense->getCategory() ?? 'Uncategorized';
                $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $eAmt;
            }
        }
        
        usort($expenses, fn($a, $b) => $b->getExpenseDate() <=> $a->getExpenseDate());
        arsort($categoryTotals);
        
        $biggestCategory = !empty($categoryTotals) ? array_key_first($categoryTotals) : 'N/A';
        $avgExpense = count($expenses) > 0 ? array_sum(array_map(fn($e) => (float)$e->getAmount(), $expenses)) / count($expenses) : 0;
        
        $healthScore = count($scores) > 0 ? round(array_sum($scores) / count($scores)) : 0;
        if ($healthScore >= 80) $healthStatus = 'Excellent';
        elseif ($healthScore >= 60) $healthStatus = 'Good';
        elseif ($healthScore >= 40) $healthStatus = 'Fair';
        else $healthStatus = 'Poor';
        
        $html = $this->renderView('budget/pdf_report.html.twig', [
            'budgets' => $budgets,
            'expenses' => $expenses,
            'totalBudget' => $totalBudget,
            'totalSpent' => $totalSpent,
            'totalRemaining' => $totalBudget - $totalSpent,
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'avgExpense' => $avgExpense,
            'highestExpense' => $highestExpense,
            'biggestCategory' => $biggestCategory
        ]);
        
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');
        $pdfOptions->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($pdfOptions);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="financial_report.pdf"'
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

    return $this->render('expense/index.html.twig', [
        'expenses' => $expenses,
        'budgets'  => $budgets,   
    ]);
}

#[Route('/expenses/new', name: 'expense_new', methods: ['GET', 'POST'])]
public function newExpense(
    Request $request,
    EntityManagerInterface $em,
    BudgetRepository $budgetRepo,
    MailerInterface $mailer
): Response {
    $budgets = $budgetRepo->findBy(['user' => $this->getUser()]);
    $preselectedBudget = $request->query->get('budget_id');

    if ($request->isMethod('POST')) {
        $budgetId = $request->request->get('budget_id');
        $budget   = $budgetRepo->find($budgetId);

        if (!$budget || $budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $expense = new Expense();
        $expense->setBudget($budget);
        $expense->setAmount($request->request->get('amount'));
        $expense->setCategory($request->request->get('category') ?: null);
        $expense->setExpenseDate(new \DateTime($request->request->get('expense_date')));
        $expense->setDescription($request->request->get('description') ?: null);

        $oldSpent  = (float)$budget->getSpentAmount();
        $newSpent  = $oldSpent + (float)$expense->getAmount();
        $budgetAmt = (float)$budget->getAmount();

        $budget->setSpentAmount((string)$newSpent);

        $em->persist($expense);
        $em->flush();

        // ── Send email ONLY when budget first gets exceeded ──
        if ($newSpent > $budgetAmt && $oldSpent <= $budgetAmt) {
            try {
                $userEmail = $this->getUser()->getEmail();

                $email = (new TemplatedEmail())
                    ->from('noreply@financeapp.com')
                    ->to($userEmail)
                    ->subject('⚠️ Budget Exceeded: ' . $budget->getName())
                    ->htmlTemplate('budget/budget_exceeded.html.twig')
                    ->context([
                        'budgetName'   => $budget->getName(),
                        'amount'       => number_format($budgetAmt, 2),
                        'spentAmount'  => number_format($newSpent, 2),
                        'overspentBy'  => number_format($newSpent - $budgetAmt, 2),
                        'lastExpense'  => number_format((float)$expense->getAmount(), 2),
                        'lastCategory' => $expense->getCategory() ?? 'Uncategorized',
                    ]);

                $mailer->send($email);
                $this->addFlash('warning', '⚠️ Budget "' . $budget->getName() . '" exceeded! Alert email sent.');

            } catch (\Exception $e) {
                // don't break the app if email fails
            }
        }

        $this->addFlash('success', 'Expense added.');
        return $this->redirectToRoute('finance_expenses');
    }

    return $this->render('expense/new.html.twig', [
        'budgets'           => $budgets,
        'preselectedBudget' => $preselectedBudget,
    ]);
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

    // Build events for FullCalendar
    $events = [];
    foreach ($bills as $bill) {
        $day = str_pad($bill->getDueDay(), 2, '0', STR_PAD_LEFT);
        $month = (new \DateTime())->format('Y-m');
        $events[] = [
            'id'              => $bill->getId(),
            'title'           => $bill->getName() . ' — ' . number_format((float)$bill->getAmount(), 2) . ' TND',
            'start'           => $month . '-' . $day,
            'backgroundColor' => $bill->getStatus() === 'PAID' ? '#f8f9fa' : '#e7f1ff',
            'borderColor'     => $bill->getStatus() === 'PAID' ? '#ced4da' : '#b6d4fe',
            'textColor'       => $bill->getStatus() === 'PAID' ? '#6c757d' : '#084298',
            'extendedProps'   => [
                'status'    => $bill->getStatus(),
                'amount'    => $bill->getAmount(),
                'frequency' => $bill->getFrequency(),
                'category'  => $bill->getCategory(),
            ],
        ];
    }

    return $this->render('bill/index.html.twig', [
        'bills'   => $bills,
        'budgets' => $budgets,
        'events'  => json_encode($events),
        'currentMonth' => (int)(new \DateTime())->format('n'),
        'currentYear'  => (int)(new \DateTime())->format('Y'),
        'currentDay'   => (int)(new \DateTime())->format('j'),
        'daysInMonth'  => (int)(new \DateTime())->format('t'),
        'firstDayOfMonth' => (int)(new \DateTime('first day of this month'))->format('N'),
    ]);
}

#[Route('/bills/new', name: 'bill_new', methods: ['GET', 'POST'])]
public function newBill(Request $request, EntityManagerInterface $em, BudgetRepository $budgetRepo): Response
{
    $budgets = $budgetRepo->findBy(['user' => $this->getUser()]);

    if ($request->isMethod('POST')) {
        $budgetId = $request->request->get('budget_id');
        $budget = $budgetRepo->find($budgetId);

        if (!$budget || $budget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

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
        return $this->redirectToRoute('finance_bills');
    }

    return $this->render('bill/new.html.twig', ['budgets' => $budgets]);
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
        // add bill amount to budget spentAmount
        if ($bill->getBudget() && $bill->getStatus() !== 'PAID') {
            $budget = $bill->getBudget();
            $budget->setSpentAmount(
                (string)((float)$budget->getSpentAmount() + (float)$bill->getAmount())
            );

            // also create an expense entry
            $expense = new Expense();
            $expense->setBudget($budget);
            $expense->setAmount($bill->getAmount());
            $expense->setCategory($bill->getCategory() ?? 'Bill');
            $expense->setExpenseDate(new \DateTime());
            $expense->setDescription('Bill payment: ' . $bill->getName());
            $em->persist($expense);
        }

        $bill->setStatus('PAID');
        $em->flush();
        $this->addFlash('success', 'Bill marked as paid and added to expenses.');
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
    #[Route('/test-email', name: 'test_email', methods: ['GET'])]
public function testEmail(MailerInterface $mailer): Response
{
    try {
        $email = (new TemplatedEmail())
            ->from('noreply@financeapp.com')
            ->to('test@test.com')
            ->subject('Test Email from Finance App')
            ->htmlTemplate('budget/budget_exceeded.html.twig')
            ->context([
                'budgetName'   => 'Test Budget',
                'amount'       => '100.00',
                'spentAmount'  => '150.00',
                'overspentBy'  => '50.00',
                'lastExpense'  => '50.00',
                'lastCategory' => 'Food & Dining',
            ]);

        $mailer->send($email);
        return new Response('✅ Email sent! Check Mailtrap.');

    } catch (\Exception $e) {
        return new Response('❌ Error: ' . $e->getMessage());
    }
}
}
