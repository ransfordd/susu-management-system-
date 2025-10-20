<?php
/**
 * CycleCalculator - Calendar-Based Monthly Cycle Logic
 * 
 * This class handles the calculation of completed cycles based on calendar months.
 * Each cycle corresponds to one calendar month (e.g., September 1-30, October 1-31, etc.)
 * 
 * Collections are allocated chronologically to fill monthly cycles, with overflow
 * from subsequent months used to complete earlier incomplete cycles.
 */

class CycleCalculator {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Get the number of completed cycles for a client
     * 
     * @param int $clientId The client ID
     * @return int Number of completed cycles
     */
    public function getCompletedCyclesCount(int $clientId): int {
        $cycles = $this->calculateClientCycles($clientId);
        return count(array_filter($cycles, fn($cycle) => $cycle['is_complete']));
    }
    
    /**
     * Calculate all monthly cycles for a client based on calendar months
     * 
     * @param int $clientId The client ID
     * @return array Array of cycle data with completion status
     */
    public function calculateClientCycles(int $clientId): array {
        // Get all collections for this client, ordered chronologically
        $stmt = $this->pdo->prepare('
            SELECT 
                dc.collection_date,
                dc.collected_amount,
                dc.day_number,
                dc.collection_status,
                sc.daily_amount,
                sc.id as cycle_id
            FROM daily_collections dc
            JOIN susu_cycles sc ON dc.susu_cycle_id = sc.id
            WHERE sc.client_id = ? 
            AND dc.collection_status = "collected"
            ORDER BY dc.collection_date ASC
        ');
        $stmt->execute([$clientId]);
        $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($collections)) {
            return [];
        }
        
        // Get the earliest and latest collection dates
        $firstDate = new DateTime($collections[0]['collection_date']);
        $lastDate = new DateTime($collections[count($collections) - 1]['collection_date']);
        
        // Generate calendar months from first collection to last collection
        $months = $this->generateMonths($firstDate, $lastDate);
        
        // Allocate collections to calendar months
        $cycles = $this->allocateCollectionsToMonths($months, $collections);
        
        return $cycles;
    }
    
    /**
     * Get detailed cycle information for display
     * 
     * @param int $clientId The client ID
     * @return array Detailed cycle information with daily breakdowns
     */
    public function getDetailedCycles(int $clientId): array {
        $cycles = $this->calculateClientCycles($clientId);
        
        // Enhance with daily collection details
        foreach ($cycles as &$cycle) {
            $cycle['daily_collections'] = $this->getDailyCollectionsForMonth(
                $clientId,
                $cycle['start_date'],
                $cycle['end_date']
            );
        }
        
        return $cycles;
    }
    
    /**
     * Generate array of calendar months between two dates
     * 
     * @param DateTime $startDate First date
     * @param DateTime $endDate Last date
     * @return array Array of month definitions
     */
    private function generateMonths(DateTime $startDate, DateTime $endDate): array {
        $months = [];
        $current = clone $startDate;
        $current->modify('first day of this month');
        
        $end = clone $endDate;
        $end->modify('last day of this month');
        
        while ($current <= $end) {
            $year = (int)$current->format('Y');
            $month = (int)$current->format('m');
            $daysInMonth = (int)$current->format('t');
            
            $monthStart = clone $current;
            $monthEnd = clone $current;
            $monthEnd->modify('last day of this month');
            
            $months[] = [
                'year' => $year,
                'month' => $month,
                'month_name' => $current->format('F Y'),
                'start_date' => $monthStart->format('Y-m-d'),
                'end_date' => $monthEnd->format('Y-m-d'),
                'days_required' => $daysInMonth,
                'collections' => []
            ];
            
            $current->modify('first day of next month');
        }
        
        return $months;
    }
    
    /**
     * Allocate collections to calendar months chronologically
     * 
     * Collections are allocated in order, filling each month sequentially.
     * If a month is not filled, collections from subsequent months are used to complete it.
     * 
     * @param array $months Array of month definitions
     * @param array $collections Array of collection records
     * @return array Array of cycles with completion status
     */
    private function allocateCollectionsToMonths(array $months, array $collections): array {
        $cycles = [];
        $availableCollections = $collections;
        
        foreach ($months as $month) {
            $cycle = [
                'month' => $month['month'],
                'year' => $month['year'],
                'month_name' => $month['month_name'],
                'start_date' => $month['start_date'],
                'end_date' => $month['end_date'],
                'days_required' => $month['days_required'],
                'days_collected' => 0,
                'is_complete' => false,
                'collections' => [],
                'total_amount' => 0.0
            ];
            
            // Take collections to fill this month
            $collectionsNeeded = $month['days_required'];
            $collectionsForMonth = array_splice($availableCollections, 0, $collectionsNeeded);
            
            $cycle['collections'] = $collectionsForMonth;
            $cycle['days_collected'] = count($collectionsForMonth);
            $cycle['is_complete'] = ($cycle['days_collected'] >= $cycle['days_required']);
            $cycle['total_amount'] = array_sum(array_column($collectionsForMonth, 'collected_amount'));
            
            $cycles[] = $cycle;
        }
        
        return $cycles;
    }
    
    /**
     * Get daily collections for a specific month
     * 
     * @param int $clientId The client ID
     * @param string $startDate Month start date (Y-m-d)
     * @param string $endDate Month end date (Y-m-d)
     * @return array Array of daily collections
     */
    private function getDailyCollectionsForMonth(int $clientId, string $startDate, string $endDate): array {
        // Get the cycle ID for this month based on the cycle's start_date and end_date
        $cycleStmt = $this->pdo->prepare('
            SELECT id FROM susu_cycles 
            WHERE client_id = ? 
            AND start_date = ? 
            AND end_date = ?
            LIMIT 1
        ');
        $cycleStmt->execute([$clientId, $startDate, $endDate]);
        $cycle = $cycleStmt->fetch();
        
        if (!$cycle) {
            // Fallback: Find any active cycle for this client if exact date match fails
            $fallbackStmt = $this->pdo->prepare('
                SELECT id FROM susu_cycles 
                WHERE client_id = ? 
                AND status = "active"
                ORDER BY created_at DESC
                LIMIT 1
            ');
            $fallbackStmt->execute([$clientId]);
            $cycle = $fallbackStmt->fetch();
        }
        
        if (!$cycle) {
            return [];
        }
        
        $stmt = $this->pdo->prepare('
            SELECT 
                dc.collection_date,
                dc.collected_amount,
                dc.collection_status,
                dc.day_number,
                a.agent_code,
                CONCAT(u.first_name, " ", u.last_name) as agent_name
            FROM daily_collections dc
            LEFT JOIN agents a ON dc.collected_by = a.id
            LEFT JOIN users u ON a.user_id = u.id
            WHERE dc.susu_cycle_id = ? 
            AND dc.collection_status = "collected"
            ORDER BY dc.day_number ASC, dc.collection_date ASC
        ');
        $stmt->execute([$cycle['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get current cycle information for a client
     * 
     * @param int $clientId The client ID
     * @return array|null Current cycle data or null if no cycles
     */
    public function getCurrentCycle(int $clientId): ?array {
        $cycles = $this->calculateClientCycles($clientId);
        
        if (empty($cycles)) {
            return null;
        }
        
        // Get the last (most recent) cycle
        $currentCycle = end($cycles);
        
        // If the last cycle is complete, there's no current active cycle
        if ($currentCycle['is_complete']) {
            return null;
        }
        
        return $currentCycle;
    }
    
    /**
     * Get summary statistics for a client's cycles
     * 
     * @param int $clientId The client ID
     * @return array Summary statistics
     */
    public function getCycleSummary(int $clientId): array {
        $cycles = $this->calculateClientCycles($clientId);
        
        $completedCycles = array_filter($cycles, fn($c) => $c['is_complete']);
        $incompleteCycles = array_filter($cycles, fn($c) => !$c['is_complete']);
        
        $totalCollected = array_sum(array_column($cycles, 'total_amount'));
        $totalDaysCollected = array_sum(array_column($cycles, 'days_collected'));
        
        return [
            'total_cycles' => count($cycles),
            'completed_cycles' => count($completedCycles),
            'incomplete_cycles' => count($incompleteCycles),
            'total_collected' => $totalCollected,
            'total_days_collected' => $totalDaysCollected,
            'current_cycle' => $this->getCurrentCycle($clientId)
        ];
    }
}

