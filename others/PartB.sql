-- ==============================================================================
-- MARKET WATCHLIST HISTORICAL DATA QUERIES (MARIADB OPTIMIZED)
-- ==============================================================================

-- 1. Latest close per symbol
-- Returns each symbol with its most recent trade_date and close price using a window function partition.
WITH RankedPrices AS (
  SELECT 
    symbol,
    trade_date,
    close,
    ROW_NUMBER() OVER(PARTITION BY symbol ORDER BY trade_date DESC) as row_num
  FROM historical_prices
)
SELECT 
  symbol,
  trade_date,
  close
FROM RankedPrices
WHERE row_num = 1;


-- 2. Top 5 symbols by liquidity
-- Calculates the highest average daily volume strictly within the June 2026 timeframe.
SELECT 
  symbol,
  AVG(volume) AS avg_daily_volume
FROM historical_prices
WHERE trade_date BETWEEN '2026-06-01' AND '2026-06-30'
GROUP BY symbol
ORDER BY avg_daily_volume DESC
LIMIT 5;


-- 3. Upsert execution
-- Inserts a new day's row, or updates the columns via standard VALUES() calls if (symbol, trade_date) matches.
INSERT INTO historical_prices (symbol, trade_date, open, high, low, close, volume)
VALUES ('AAPL', '2026-07-20', 240.0000, 245.5000, 239.1000, 244.2000, 48000000)
ON DUPLICATE KEY UPDATE
  open   = VALUES(open),
  high   = VALUES(high),
  low    = VALUES(low),
  close  = VALUES(close),
  volume = VALUES(volume);


-- 4. Data quality check anomaly reporting
-- Scans for and explicitly flags logical pricing or volume inconsistencies across historical records.
SELECT 
  symbol,
  trade_date,
  open,
  high,
  low,
  close,
  volume,
  CASE 
    WHEN high < low THEN 'High price is less than Low price'
    WHEN close < low THEN 'Close price is below Low price boundary'
    WHEN close > high THEN 'Close price is above High price boundary'
    WHEN open < low THEN 'Open price is below Low price boundary'
    WHEN open > high THEN 'Open price is above High price boundary'
    WHEN volume < 0 THEN 'Negative volume detected'
    ELSE 'Unknown Inconsistency'
  END AS anomaly_reason
FROM historical_prices
WHERE high < low
   OR close < low
   OR close > high
   OR open < low
   OR open > high
   OR volume < 0;