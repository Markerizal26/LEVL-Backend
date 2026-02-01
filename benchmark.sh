#!/bin/bash

# ==================================================
# BENCHMARK SCRIPT: Laravel Octane vs PHP-FPM
# Untuk Tugas Akhir - Perbandingan Performa
# ==================================================

# Konfigurasi
OCTANE_PORT=8000
SERVE_PORT=9001
REQUESTS=1000
CONCURRENCY=20
WARMUP_REQUESTS=10
RUNS=3
OUTPUT_DIR="tests/reports/benchmark"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Warna output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=================================================="
echo "BENCHMARK: Laravel Octane (Swoole) vs PHP artisan serve"
echo "Timestamp: $(date)"
echo -e "==================================================${NC}"
echo ""

# Buat direktori output
mkdir -p $OUTPUT_DIR

# Fungsi untuk flush Redis cache
flush_redis() {
    echo -e "${YELLOW}Flushing Redis cache...${NC}"
    redis-cli FLUSHALL > /dev/null 2>&1
    sleep 1
}

# Fungsi untuk warm-up
warmup() {
    local port=$1
    local name=$2
    echo -e "${YELLOW}Warming up $name (port $port)...${NC}"
    for i in $(seq 1 $WARMUP_REQUESTS); do
        curl -s "http://127.0.0.1:$port/api/v1/benchmark/users" > /dev/null
    done
    sleep 2
}

# Fungsi untuk menjalankan benchmark
run_benchmark() {
    local port=$1
    local name=$2
    local run_num=$3
    local output_file="$OUTPUT_DIR/${name}_run${run_num}_${TIMESTAMP}.txt"
    
    echo -e "${GREEN}Running $name benchmark (Run $run_num)...${NC}"
    ab -n $REQUESTS -c $CONCURRENCY "http://127.0.0.1:$port/api/v1/benchmark/users" > "$output_file" 2>&1
    
    # Extract metrics
    local rps=$(grep "Requests per second" "$output_file" | awk '{print $4}')
    local mean_latency=$(grep "Time per request" "$output_file" | head -1 | awk '{print $4}')
    local p50=$(grep "50%" "$output_file" | awk '{print $2}')
    local p95=$(grep "95%" "$output_file" | awk '{print $2}')
    local p99=$(grep "99%" "$output_file" | awk '{print $2}')
    local failed=$(grep "Failed requests" "$output_file" | awk '{print $3}')
    
    echo "  RPS: $rps | Mean: ${mean_latency}ms | P50: ${p50}ms | P95: ${p95}ms | P99: ${p99}ms | Failed: $failed"
    
    # Return values for averaging
    echo "$rps,$mean_latency,$p50,$p95,$p99,$failed" >> "$OUTPUT_DIR/${name}_results_${TIMESTAMP}.csv"
}

# Header CSV
echo "RPS,Mean_Latency,P50,P95,P99,Failed" > "$OUTPUT_DIR/octane_results_${TIMESTAMP}.csv"
echo "RPS,Mean_Latency,P50,P95,P99,Failed" > "$OUTPUT_DIR/serve_results_${TIMESTAMP}.csv"

echo ""
echo -e "${GREEN}=== PHASE 1: Laravel Octane (Swoole) - Port $OCTANE_PORT ===${NC}"
echo ""

for run in $(seq 1 $RUNS); do
    flush_redis
    warmup $OCTANE_PORT "octane"
    run_benchmark $OCTANE_PORT "octane" $run
    sleep 3
done

echo ""
echo -e "${GREEN}=== PHASE 2: PHP artisan serve (PHP-FPM equivalent) - Port $SERVE_PORT ===${NC}"
echo ""

for run in $(seq 1 $RUNS); do
    flush_redis
    warmup $SERVE_PORT "serve"
    run_benchmark $SERVE_PORT "serve" $run
    sleep 3
done

echo ""
echo -e "${GREEN}=== SUMMARY ===${NC}"
echo ""
echo "Results saved to: $OUTPUT_DIR"
echo ""

# Generate summary
echo -e "${YELLOW}Laravel Octane results:${NC}"
cat "$OUTPUT_DIR/octane_results_${TIMESTAMP}.csv"
echo ""
echo -e "${YELLOW}PHP artisan serve results:${NC}"
cat "$OUTPUT_DIR/serve_results_${TIMESTAMP}.csv"

echo ""
echo -e "${GREEN}Benchmark completed at $(date)${NC}"
