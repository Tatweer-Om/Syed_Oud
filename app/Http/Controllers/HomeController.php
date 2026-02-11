<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Stock;
use App\Models\ColorSize;
use App\Models\StockColor;
use App\Models\StockSize;
use App\Models\Settings;
use App\Models\Expense;

class HomeController extends Controller
{
    public function index(){
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        // Today: only expenses (no revenue from removed modules)
        $todayExpenses = Expense::whereDate('expense_date', $today)->sum('amount');
        $todayRevenue = 0;
        $todayNetProfit = -$todayExpenses;
        
        // This month: expenses only
        $monthExpenses = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $monthRevenue = 0;
        $monthNetProfit = -$monthExpenses;
        
        $totalRevenue = $monthRevenue;
        $totalExpenses = $monthExpenses;
        $revenueMinusExpense = $totalRevenue - $totalExpenses;
        
        $ordersWithTailor = 0; // removed
        $currentYear = Carbon::now()->year;
        $monthlyData = $this->calculateMonthlyData($currentYear);
        
        return view('dashboard.dashboard', compact(
            'todayNetProfit',
            'monthNetProfit',
            'revenueMinusExpense',
            'ordersWithTailor',
            'todayRevenue',
            'todayExpenses',
            'totalRevenue',
            'totalExpenses',
            'monthlyData',
            'currentYear'
        ));
    }
    
    private function calculateMonthlyData($year)
    {
        $monthlyRevenue = [];
        $monthlyExpenses = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth();
            
            $monthlyRevenue[] = 0;
            $monthlyExpenses[] = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        }
        
        return [
            'revenue' => $monthlyRevenue,
            'expenses' => $monthlyExpenses
        ];
    }
    
    public function getMonthlyData(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $monthlyData = $this->calculateMonthlyData($year);
        
        return response()->json([
            'success' => true,
            'revenue' => $monthlyData['revenue'],
            'expenses' => $monthlyData['expenses']
        ]);
    }
    
    public function getLowStockItems()
    {
        try {
            $lowStockItems = [];
            $lowStockThreshold = 3;
            
            $stocks = Stock::with(['colorSizes', 'colors', 'sizes'])
                ->get();
            
            foreach ($stocks as $stock) {
                $totalQty = 0;
                
                if ($stock->mode === 'color_size') {
                    $totalQty = ColorSize::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'color') {
                    $totalQty = StockColor::where('stock_id', $stock->id)->sum('qty');
                } elseif ($stock->mode === 'size') {
                    $totalQty = StockSize::where('stock_id', $stock->id)->sum('qty');
                } else {
                    $totalQty = (int)($stock->quantity ?? 0);
                }
                
                if ($totalQty <= $lowStockThreshold) {
                    $percentage = $lowStockThreshold > 0 
                        ? ($totalQty / $lowStockThreshold) * 100 
                        : 0;
                    
                    $lowStockItems[] = [
                        'id' => $stock->id,
                        'stock_code' => $stock->stock_code ?? 'N/A',
                        'design_name' => $stock->design_name ?? $stock->stock_code ?? 'N/A',
                        'remaining' => $totalQty,
                        'threshold' => $lowStockThreshold,
                        'percentage' => min($percentage, 100),
                    ];
                }
            }
            
            usort($lowStockItems, function($a, $b) {
                return $a['remaining'] <=> $b['remaining'];
            });
            
            $lowStockItems = array_slice($lowStockItems, 0, 10);
            
            return response()->json([
                'success' => true,
                'items' => $lowStockItems
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching low stock items: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data',
                'items' => []
            ], 500);
        }
    }
    
    public function getNotifications()
    {
        try {
            $notifications = [];
            
            // Low stock items
            $lowStockItems = Stock::where('quantity', '<=', 2)
                ->where('quantity', '>', 0)
                ->with('category')
                ->get();
            
            foreach ($lowStockItems as $stock) {
                $categoryName = $stock->category ? 
                    (session('locale') === 'ar' ? $stock->category->category_name_ar : $stock->category->category_name_en) : 
                    'N/A';
                
                $stockName = $stock->design_name ?? $stock->stock_code ?? 'N/A';
                $quantityText = str_replace(':quantity', (string)$stock->quantity, trans('messages.remaining_quantity_pieces', [], session('locale')));
                
                $notifications[] = [
                    'type' => 'low_stock',
                    'icon' => 'inventory',
                    'iconColor' => 'text-amber-500',
                    'title' => trans('messages.low_stock_stock', [], session('locale')),
                    'message' => $stockName . ' (' . $categoryName . ') - ' . $quantityText,
                    'time' => $stock->updated_at ? $stock->updated_at->diffForHumans() : '',
                    'link' => url('view_stock')
                ];
            }
            
            usort($notifications, function($a, $b) {
                return strtotime($b['time'] ?? 0) <=> strtotime($a['time'] ?? 0);
            });
            
            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'count' => count($notifications)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching notifications: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'notifications' => [],
                'count' => 0
            ]);
        }
    }
}
