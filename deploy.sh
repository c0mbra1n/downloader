#!/bin/bash

# Define colors for better readability
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Loop indefinitely until user chooses to exit
while true; do
    # Header
    clear
    echo -e "${BLUE}=========================================${NC}"
    echo -e "${BLUE}   AditiaCloud Store - Deployment Menu   ${NC}"
    echo -e "${BLUE}=========================================${NC}"
    echo ""

    # Menu Options
    echo "1. 📥 Git Pull Only (Update Code)"
    echo "2. 🗄️  Run Database Migrations"
    echo "3. 🧹 Clear & Rebuild Cache (Config, View, Route)"
    echo "4. 🚀 FULL DEPLOY (Pull + Migrate + Cache)"
    echo "5. ❌ Exit"
    echo ""
    echo -e "${BLUE}=========================================${NC}"

    # Read User Input
    read -p "Select an option [1-5]: " option
    echo ""

    case $option in
        1)
            echo -e "${YELLOW}Executing: git pull origin main${NC}"
            git pull origin main
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✅ Git Pull Successful!${NC}"
            else
                echo -e "${RED}❌ Git Pull Failed!${NC}"
            fi
            ;;
        2)
            echo -e "${YELLOW}Executing: php artisan migrate --force${NC}"
            php artisan migrate --force
            if [ $? -eq 0 ]; then
                echo -e "${GREEN}✅ Migration Successful!${NC}"
            else
                echo -e "${RED}❌ Migration Failed!${NC}"
            fi
            ;;
        3)
            echo -e "${YELLOW}Clearing and rebuilding cache...${NC}"
            php artisan optimize:clear
            php artisan config:cache
            php artisan view:cache
            php artisan route:cache
            echo -e "${GREEN}✅ Cache Rebuilt Successfully!${NC}"
            ;;
        4)
            echo -e "${GREEN}🚀 Starting Full Deployment...${NC}"
            echo ""
            
            # Step 1: Git Pull
            echo -e "${BLUE}[1/5] Updating Code...${NC}"
            git pull origin main
            if [ $? -ne 0 ]; then
                echo -e "${RED}❌ Git Pull Failed! Aborting.${NC}"
                # Don't exit script, just break to menu
            else
                # Step 2: Migrate
                echo -e "${BLUE}[2/5] Updating Database...${NC}"
                php artisan migrate --force
                
                # Step 3: Cache
                echo -e "${BLUE}[3/5] Optimizing System...${NC}"
                php artisan optimize:clear
                php artisan config:cache
                php artisan view:cache
                php artisan route:cache
                
                # Step 4: Permissions
                echo -e "${BLUE}[4/5] Fixing Permissions...${NC}"
                chmod -R 777 storage bootstrap/cache
                echo -e "${GREEN}✅ Permissions Fixed!${NC}"

                # Step 5: Restart Worker
                echo -e "${BLUE}[5/5] Restarting Queue Worker...${NC}"
                supervisorctl restart dl-ragos-worker:*
                echo -e "${GREEN}✅ Worker Restarted!${NC}"
                
                echo ""
                echo -e "${GREEN}✨ FULL DEPLOYMENT COMPLETED SUCCESSFULLY! ✨${NC}"
            fi
            ;;
        5)
            echo "Exiting..."
            exit 0
            ;;
        *)
            echo -e "${RED}Invalid option! Please select 1-5.${NC}"
            ;;
    esac

    echo ""
    read -p "Press Enter to return to menu..."
done
