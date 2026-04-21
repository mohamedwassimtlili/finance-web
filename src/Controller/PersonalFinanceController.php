<?php
// C:\Users\PC\Desktop\finance-web\src\Controller\PersonalFinanceController.php

namespace App\Controller;

use App\Entity\Budget;
use App\Entity\Expense;
use App\Entity\Bill;
use App\Repository\BudgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/finance', name: 'finance_')]
class PersonalFinanceController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(BudgetRepository $budgetRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Please log in to view your dashboard.');
        }

        $budgets = $budgetRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        // Calculate financial health score
        $healthScore = $this->calculateHealthScore($budgets);
        $healthStatus = $this->getHealthStatus($healthScore);
        
        // Detect spending patterns
        $spendingPatterns = $this->detectSpendingPatterns($budgets);
        
        // Generate smart notifications
        $notifications = $this->generateSmartNotifications($budgets);
        
        // Calculate budget summary
        $budgetSummary = $this->calculateBudgetSummary($budgets);
        
        // Calculate spending forecast
        $forecast = $this->calculateForecast($budgets);
        
        // Prepare budget status with percentages
        $budgetsWithStatus = $this->prepareBudgetsWithStatus($budgets);
        
        return $this->render('budget/dashboard.html.twig', [
            'budgets' => $budgetsWithStatus,
            'healthScore' => $healthScore,
            'healthStatus' => $healthStatus,
            'spendingPatterns' => $spendingPatterns,
            'notifications' => $notifications,
            'budgetSummary' => $budgetSummary,
            'forecast' => $forecast,
        ]);
    }
    
    /**
     * Calculate Financial Health Score (0-100)
     * Based on budget usage percentages
     */
    private function calculateHealthScore(array $budgets): int
    {
        if (empty($budgets)) {
            return 0;
        }
        
        $totalScore = 0;
        $validBudgets = 0;
        
        foreach ($budgets as $budget) {
            $amount = (float)$budget->getAmount();
            if ($amount == 0) {
                continue;
            }
            
            $spentAmount = (float)$budget->getSpentAmount();
            $percentage = ($spentAmount / $amount) * 100;
            
            // Score based on percentage used
            if ($percentage <= 50) {
                $score = 100;
            } elseif ($percentage <= 75) {
                $score = 80;
            } elseif ($percentage <= 90) {
                $score = 60;
            } elseif ($percentage <= 100) {
                $score = 40;
            } else {
                $score = 10;
            }
            
            $totalScore += $score;
            $validBudgets++;
        }
        
        return $validBudgets > 0 ? (int)($totalScore / $validBudgets) : 0;
    }
    
    /**
     * Get health status based on score
     */
    private function getHealthStatus(int $score): array
    {
        if ($score >= 80) {
            return ['text' => 'Excellent', 'class' => 'success', 'icon' => '🎉'];
        } elseif ($score >= 60) {
            return ['text' => 'Good', 'class' => 'info', 'icon' => '👍'];
        } elseif ($score >= 40) {
            return ['text' => 'Fair', 'class' => 'warning', 'icon' => '⚠️'];
        } else {
            return ['text' => 'Poor', 'class' => 'danger', 'icon' => '🔴'];
        }
    }
    
    /**
     * Detect spending patterns from expenses
     */
    private function detectSpendingPatterns(array $budgets): array
    {
        $allExpenses = [];
        $categoryTotals = [];
        $dailyTotals = [];
        $expenseAmounts = [];
        
        foreach ($budgets as $budget) {
            foreach ($budget->getExpenses() as $expense) {
                $amount = (float)$expense->getAmount();
                $category = $expense->getCategory() ?: 'Uncategorized';
                $date = $expense->getExpenseDate();
                $dayOfWeek = $date->format('l');
                
                $allExpenses[] = $expense;
                $categoryTotals[$category] = ($categoryTotals[$category] ?? 0) + $amount;
                $dailyTotals[$dayOfWeek] = ($dailyTotals[$dayOfWeek] ?? 0) + $amount;
                $expenseAmounts[] = $amount;
            }
        }
        
        // Find biggest spending category
        $biggestCategory = !empty($categoryTotals) 
            ? array_keys($categoryTotals, max($categoryTotals))[0] 
            : null;
        
        // Find most expensive day
        $mostExpensiveDay = !empty($dailyTotals) 
            ? array_keys($dailyTotals, max($dailyTotals))[0] 
            : null;
        
        // Calculate average expense
        $averageExpense = !empty($expenseAmounts) 
            ? round(array_sum($expenseAmounts) / count($expenseAmounts), 2) 
            : 0;
        
        // Find highest expense
        $highestExpense = !empty($expenseAmounts) ? max($expenseAmounts) : 0;
        
        // Count total expenses
        $totalExpenses = count($allExpenses);
        
        // Get top 3 categories
        arsort($categoryTotals);
        $topCategories = array_slice($categoryTotals, 0, 3, true);
        
        return [
            'biggest_category' => $biggestCategory,
            'most_expensive_day' => $mostExpensiveDay,
            'average_expense' => $averageExpense,
            'highest_expense' => $highestExpense,
            'total_expenses' => $totalExpenses,
            'top_categories' => $topCategories,
            'category_totals' => $categoryTotals,
            'daily_totals' => $dailyTotals,
        ];
    }
    
    /**
     * Generate smart notifications based on budget status
     */
    private function generateSmartNotifications(array $budgets): array
    {
        $notifications = [];
        
        foreach ($budgets as $budget) {
            $amount = (float)$budget->getAmount();
            if ($amount == 0) {
                continue;
            }
            
            $spentAmount = (float)$budget->getSpentAmount();
            $percentage = ($spentAmount / $amount) * 100;
            $remaining = $amount - $spentAmount;
            $budgetName = $budget->getName();
            
            // Overspent alert
            if ($percentage > 100) {
                $notifications[] = [
                    'type' => 'danger',
                    'icon' => '🔴',
                    'message' => sprintf(
                        'OVERSPENT: You have exceeded your "%s" budget by $%.2f!',
                        $budgetName,
                        abs($remaining)
                    ),
                    'budget_id' => $budget->getId()
                ];
            }
            // Near limit warning (90-100%)
            elseif ($percentage >= 90 && $percentage <= 100) {
                $notifications[] = [
                    'type' => 'warning',
                    'icon' => '⚠️',
                    'message' => sprintf(
                        'NEAR LIMIT: "%s" budget is at %.1f%% usage. Only $%.2f remaining!',
                        $budgetName,
                        $percentage,
                        $remaining
                    ),
                    'budget_id' => $budget->getId()
                ];
            }
            // Warning (75-90%)
            elseif ($percentage >= 75 && $percentage < 90) {
                $notifications[] = [
                    'type' => 'info',
                    'icon' => 'ℹ️',
                    'message' => sprintf(
                        'WARNING: "%s" budget is at %.1f%% usage. Consider reducing expenses.',
                        $budgetName,
                        $percentage
                    ),
                    'budget_id' => $budget->getId()
                ];
            }
            // On track with low usage (<30%)
            elseif ($percentage < 30 && $spentAmount > 0) {
                $notifications[] = [
                    'type' => 'success',
                    'icon' => '✅',
                    'message' => sprintf(
                        'On Track: "%s" budget is only at %.1f%% usage. Great job!',
                        $budgetName,
                        $percentage
                    ),
                    'budget_id' => $budget->getId()
                ];
            }
        }
        
        // Add bill reminders (bills that are UNPAID)
        foreach ($budgets as $budget) {
            foreach ($budget->getBills() as $bill) {
                if ($bill->getStatus() === 'UNPAID') {
                    $currentDay = (int)date('j');
                    $dueDay = $bill->getDueDay();
                    
                    // Notify if bill is due within 5 days
                    if ($dueDay >= $currentDay && $dueDay - $currentDay <= 5) {
                        $notifications[] = [
                            'type' => 'warning',
                            'icon' => '📅',
                            'message' => sprintf(
                                'Bill Reminder: "%s" of $%.2f is due on day %d of the month.',
                                $bill->getName(),
                                (float)$bill->getAmount(),
                                $dueDay
                            ),
                            'budget_id' => $budget->getId()
                        ];
                    }
                    // Notify if bill is overdue
                    elseif ($dueDay < $currentDay) {
                        $notifications[] = [
                            'type' => 'danger',
                            'icon' => '⏰',
                            'message' => sprintf(
                                'OVERDUE BILL: "%s" of $%.2f was due on day %d. Please pay immediately!',
                                $bill->getName(),
                                (float)$bill->getAmount(),
                                $dueDay
                            ),
                            'budget_id' => $budget->getId()
                        ];
                    }
                }
            }
        }
        
        // Sort notifications by severity (danger first, then warning, info, success)
        usort($notifications, function($a, $b) {
            $severity = ['danger' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
            return $severity[$a['type']] <=> $severity[$b['type']];
        });
        
        return $notifications;
    }
    
    /**
     * Calculate overall budget summary
     */
    private function calculateBudgetSummary(array $budgets): array
    {
        $totalBudget = 0;
        $totalSpent = 0;
        $totalRemaining = 0;
        $onTrackCount = 0;
        $warningCount = 0;
        $nearLimitCount = 0;
        $overspentCount = 0;
        
        foreach ($budgets as $budget) {
            $amount = (float)$budget->getAmount();
            if ($amount == 0) {
                continue;
            }
            
            $spentAmount = (float)$budget->getSpentAmount();
            $percentage = ($spentAmount / $amount) * 100;
            
            $totalBudget += $amount;
            $totalSpent += $spentAmount;
            
            if ($percentage <= 50) {
                $onTrackCount++;
            } elseif ($percentage <= 75) {
                $warningCount++;
            } elseif ($percentage <= 90) {
                $nearLimitCount++;
            } else {
                $overspentCount++;
            }
        }
        
        $totalRemaining = $totalBudget - $totalSpent;
        $overallPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;
        
        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'total_remaining' => $totalRemaining,
            'overall_percentage' => round($overallPercentage, 1),
            'on_track_count' => $onTrackCount,
            'warning_count' => $warningCount,
            'near_limit_count' => $nearLimitCount,
            'overspent_count' => $overspentCount,
        ];
    }
    
    /**
     * Prepare budgets with their status and percentages
     */
    private function prepareBudgetsWithStatus(array $budgets): array
    {
        $result = [];
        
        foreach ($budgets as $budget) {
            $amount = (float)$budget->getAmount();
            $spentAmount = (float)$budget->getSpentAmount();
            
            if ($amount > 0) {
                $percentage = min(100, ($spentAmount / $amount) * 100);
                $status = $budget->getStatus(); // Using the entity's getStatus method
            } else {
                $percentage = 0;
                $status = 'No Budget';
            }
            
            // Get status color for UI
            $statusColor = match($status) {
                'On Track' => 'success',
                'Warning' => 'warning',
                'Near Limit' => 'info',
                'Overspent' => 'danger',
                default => 'secondary'
            };
            
            $result[] = [
                'budget' => $budget,
                'percentage' => round($percentage, 1),
                'status' => $status,
                'status_color' => $statusColor,
                'remaining' => $amount - $spentAmount,
            ];
        }
        
        return $result;
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
        
        // If it's the first day and no expenses, prevent division by zero
        if ($currentDay === 1 && $monthExpenses == 0) {
            $dailyRate = 0;
        } else {
            // Calculate daily rate based on days elapsed so far
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

    #[Route('/dashboard/export/csv', name: 'export_csv')]
    public function exportCsv(BudgetRepository $budgetRepo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Please log in.');
        }

        $budgets = $budgetRepo->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        $response = new StreamedResponse(function () use ($budgets) {
            $handle = fopen('php://output', 'w+');
            
            // Add BOM for Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");
            
            // Write Budgets summary header
            fputcsv($handle, ['--- BUDGETS SUMMARY ---']);
            fputcsv($handle, ['Budget Name', 'Category', 'Total Amount', 'Spent Amount', 'Remaining', 'Status']);
            
            foreach ($budgets as $budget) {
                $amount = (float)$budget->getAmount();
                $spentAmount = (float)$budget->getSpentAmount();
                fputcsv($handle, [
                    $budget->getName(),
                    $budget->getCategory() ?: 'Uncategorized',
                    $amount,
                    $spentAmount,
                    $amount - $spentAmount,
                    $budget->getStatus()
                ]);
            }
            
            // Add an empty line before expenses
            fputcsv($handle, []);
            fputcsv($handle, ['--- ALL EXPENSES ---']);
            fputcsv($handle, ['Date', 'Budget', 'Category', 'Description', 'Amount']);
            
            $expensesList = [];
            foreach ($budgets as $budget) {
                foreach ($budget->getExpenses() as $expense) {
                    $expensesList[] = [
                        'date' => $expense->getExpenseDate()->format('Y-m-d'),
                        'budget' => $budget->getName(),
                        'category' => $expense->getCategory() ?: 'Uncategorized',
                        'description' => $expense->getDescription(),
                        'amount' => $expense->getAmount()
                    ];
                }
            }
            
            // Sort expenses by date descending
            usort($expensesList, function($a, $b) {
                return $b['date'] <=> $a['date'];
            });
            
            foreach ($expensesList as $row) {
                fputcsv($handle, [$row['date'], $row['budget'], $row['category'], $row['description'], $row['amount']]);
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="financial_report_'.date('Y_m_d').'.csv"');
        
        return $response;
    }
}