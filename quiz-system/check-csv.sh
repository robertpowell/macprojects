#!/bin/bash
# CSV Diagnostic Script
# Usage: bash check-csv.sh filename.csv

if [ -z "$1" ]; then
    echo "Usage: bash check-csv.sh filename.csv"
    exit 1
fi

CSV_FILE="$1"

if [ ! -f "$CSV_FILE" ]; then
    echo "Error: File '$CSV_FILE' not found"
    exit 1
fi

echo "=== CSV Diagnostic Tool ==="
echo "File: $CSV_FILE"
echo ""

# Check if file exists and is readable
echo "1. File Check:"
ls -lh "$CSV_FILE"
echo ""

# Check line endings
echo "2. Line Endings:"
file "$CSV_FILE"
echo ""

# Count lines
echo "3. Total Lines:"
wc -l "$CSV_FILE"
echo ""

# Show header
echo "4. Header Row:"
head -1 "$CSV_FILE"
echo ""

# Count fields in header
echo "5. Header Field Count:"
head -1 "$CSV_FILE" | awk -F',' '{print NF " fields"}'
echo ""

# Check each row for field count
echo "6. Checking each row for missing fields..."
echo ""

awk -F',' 'NR==1 {expected=NF; print "Expected fields: " expected; next}
           NF != expected {print "Row " NR ": Has " NF " fields (expected " expected ") - PROBLEM"}' "$CSV_FILE"

echo ""

# Show specific problematic rows (around row 36)
echo "7. Showing rows 34-38 (around the problem area):"
sed -n '34,38p' "$CSV_FILE" | cat -n
echo ""

# Check for common issues
echo "8. Common Issues Check:"

# Check for blank lines
blank_lines=$(grep -c '^$' "$CSV_FILE")
echo "   - Blank lines: $blank_lines"

# Check for lines with only commas
comma_only=$(grep -c '^,*$' "$CSV_FILE")
echo "   - Comma-only lines: $comma_only"

# Check for unmatched quotes
echo "   - Checking for unmatched quotes..."
awk '{
    gsub(/\\"/,""); # Remove escaped quotes
    quotes = gsub(/"/,"&");
    if (quotes % 2 != 0) print "   Row " NR ": Unmatched quotes"
}' "$CSV_FILE"

echo ""
echo "=== Analysis Complete ==="
echo ""
echo "To see row 36 specifically:"
echo "  sed -n '36p' $CSV_FILE"
echo ""
echo "To see row 36 with visible special characters:"
echo "  sed -n '36p' $CSV_FILE | cat -A"
