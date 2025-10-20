-- Migration: Add payout transfer fields to susu_cycles table
-- Created: 2024-12-19
-- Purpose: Track automatic payout transfers to savings accounts

ALTER TABLE susu_cycles 
ADD COLUMN payout_transferred TINYINT(1) DEFAULT 0,
ADD COLUMN payout_transferred_at TIMESTAMP NULL;

-- Add index for performance
CREATE INDEX idx_payout_transferred ON susu_cycles (payout_transferred, completion_date);
