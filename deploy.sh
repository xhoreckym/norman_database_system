#!/bin/bash

# Check if commit message is provided
if [ $# -eq 0 ]; then
    echo "Error: Please provide a commit message"
    echo "Usage: ./deploy.sh \"your commit message here\""
    exit 1
fi

# Get the commit message from the first argument
COMMIT_MESSAGE="$1"

echo -e "\033[1;36mStarting deployment process...\033[0m"
echo -e "\033[1;33mCommit message: $COMMIT_MESSAGE\033[0m"

# Step 1: Commit and push current changes
echo -e "\033[1;34mCommitting and pushing changes...\033[0m"
git add -A
git commit -am "$COMMIT_MESSAGE"
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mGit commit failed. Exiting.\033[0m"
    exit 1
fi

git push
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mGit push failed. Exiting.\033[0m"
    exit 1
fi

# Step 2: Merge to main and push
echo -e "\033[1;35mSwitching to main branch...\033[0m"
git checkout main
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to checkout main branch. Exiting.\033[0m"
    exit 1
fi

echo -e "\033[1;34mPulling latest changes on main...\033[0m"
git pull --no-edit
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to pull main branch. Exiting.\033[0m"
    exit 1
fi

echo -e "\033[1;36mMerging development branch...\033[0m"
git merge development --no-edit
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to merge development branch. Exiting.\033[0m"
    exit 1
fi

echo -e "\033[1;34mPushing main branch...\033[0m"
git push
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to push main branch. Exiting.\033[0m"
    exit 1
fi

# Step 3: Switch back to development and sync
echo -e "\033[1;35mSwitching back to development...\033[0m"
git checkout development
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to checkout development branch. Exiting.\033[0m"
    exit 1
fi

echo -e "\033[1;34mPulling latest changes on development...\033[0m"
git pull --no-edit
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to pull development branch. Exiting.\033[0m"
    exit 1
fi

echo -e "\033[1;34mPushing development branch...\033[0m"
git push
if [ $? -ne 0 ]; then
    echo -e "\033[1;31mFailed to push development branch. Exiting.\033[0m"
    exit 1
fi

# Step 4: Deploy to server
echo -e "\033[1;32mDeploying to production server...\033[0m"
ssh deployer@145.223.117.219 << 'EOF'
    echo -e "\033[1;37mNavigating to project directory...\033[0m"
    cd /opt/projects/norman_database_system/
    
    echo -e "\033[1;34mPulling latest changes on server...\033[0m"
    git pull
    
    echo -e "\033[1;33mBuilding assets...\033[0m"
    docker exec -it nds-app npm run build
    
    echo -e "\033[1;36mClearing Laravel views cache...\033[0m"
    docker exec -it nds-app php artisan view:clear
    
    echo -e "\033[1;32mServer deployment completed!\033[0m"
EOF

if [ $? -eq 0 ]; then
    echo -e "\033[1;32mDeployment completed successfully!\033[0m"
else
    echo -e "\033[1;31mDeployment failed on server.\033[0m"
    exit 1
fi