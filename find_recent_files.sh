#!/bin/bash

# Script to find files created in the past 24 hours on macOS
# Usage: ./find_recent_files.sh [directory]

# Set the directory to search (default to current directory)
SEARCH_DIR="${1:-.}"

# Check if directory exists
if [ ! -d "$SEARCH_DIR" ]; then
    echo "Error: Directory '$SEARCH_DIR' does not exist"
    exit 1
fi

echo "Searching for files created in the past 24 hours in: $SEARCH_DIR"
echo "=================================================="

# Method 1: Using mdfind (Mac-specific, faster and more accurate)
if command -v mdfind &> /dev/null; then
    echo -e "\nUsing mdfind (Mac Spotlight):"
    echo "------------------------------"

    # Calculate timestamp for 24 hours ago
    TIMESTAMP=$(date -v-24H "+%Y-%m-%d %H:%M:%S")

    # Search using mdfind with creation date filter
    mdfind -onlyin "$SEARCH_DIR" "kMDItemFSCreationDate >= \$time.iso($TIMESTAMP)" 2>/dev/null

    COUNT=$(mdfind -onlyin "$SEARCH_DIR" "kMDItemFSCreationDate >= \$time.iso($TIMESTAMP)" 2>/dev/null | wc -l)
    echo -e "\nTotal files found: $COUNT"
else
    # Method 2: Using find command (fallback, works on Linux too)
    echo -e "\nUsing find command:"
    echo "-------------------"

    # Find files created in the last 24 hours
    # -type f: only files (not directories)
    # -ctime -1: creation/status change time less than 1 day ago
    find "$SEARCH_DIR" -type f -ctime -1 -print 2>/dev/null

    COUNT=$(find "$SEARCH_DIR" -type f -ctime -1 -print 2>/dev/null | wc -l)
    echo -e "\nTotal files found: $COUNT"
fi

echo "=================================================="
echo "Search complete!"
