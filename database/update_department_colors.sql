-- Update Department Colors to Pastel Colors
-- Created: 2025-03-22
-- 
-- This script updates the colors of existing departments to use more pastel/softer colors
-- Run this script against your existing database to update the colors

-- IT department - Pastel Blue
UPDATE departments SET color = '#94C2F3' WHERE name = 'IT';

-- Marketing department - Pastel Green
UPDATE departments SET color = '#A8D8AD' WHERE name = 'Marketing';

-- HR department - Pastel Pink
UPDATE departments SET color = '#F4B8B8' WHERE name = 'HR';

-- Finance department - Pastel Yellow
UPDATE departments SET color = '#FFEAAA' WHERE name = 'Finance';

-- Operations department - Pastel Teal
UPDATE departments SET color = '#9FE0E0' WHERE name = 'Operations';

-- Also update the base SQL file for future installations
-- Note: This doesn't change your current database, just the SQL file for new installations
