#!/bin/bash

# Check if commit message is provided
if [ $# -eq 0 ]; then
    echo "Error: Please provide a commit message"
    echo "Usage: ./deploy.sh \"your commit message here\""
    exit 1
fi

# Get the commit message from the first argument
COMMIT_MESSAGE="$1"

echo "🚀 Starting deployment process..."
echo "📝 Commit message: $COMMIT_MESSAGE"

# Step 1: Commit and push current changes
echo "📦 Committing and pushing changes..."
git add -A
git commit -am "$COMMIT_MESSAGE"
if [ $? -ne 0 ]; then
    echo "❌ Git commit failed. Exiting."
    exit 1
fi

git push
if [ $? -ne 0 ]; then
    echo "❌ Git push failed. Exiting."
    exit 1
fi

# Step 2: Merge to main and push
echo "🔄 Switching to main branch..."
git checkout main
if [ $? -ne 0 ]; then
    echo "❌ Failed to checkout main branch. Exiting."
    exit 1
fi

echo "⬇️ Pulling latest changes on main..."
git pull --no-edit
if [ $? -ne 0 ]; then
    echo "❌ Failed to pull main branch. Exiting."
    exit 1
fi

echo "🔀 Merging development branch..."
git merge development --no-edit
if [ $? -ne 0 ]; then
    echo "❌ Failed to merge development branch. Exiting."
    exit 1
fi

echo "⬆️ Pushing main branch..."
git push
if [ $? -ne 0 ]; then
    echo "❌ Failed to push main branch. Exiting."
    exit 1
fi

# Step 3: Switch back to development and sync
echo "🔄 Switching back to development..."
git checkout development
if [ $? -ne 0 ]; then
    echo "❌ Failed to checkout development branch. Exiting."
    exit 1
fi

echo "⬇️ Pulling latest changes on development..."
git pull --no-edit
if [ $? -ne 0 ]; then
    echo "❌ Failed to pull development branch. Exiting."
    exit 1
fi

echo "⬆️ Pushing development branch..."
git push
if [ $? -ne 0 ]; then
    echo "❌ Failed to push development branch. Exiting."
    exit 1
fi

# Step 4: Deploy to server
echo "🌐 Deploying to production server..."
ssh deployer@145.223.117.219 << 'EOF'
    echo "📁 Navigating to project directory..."
    cd /opt/projects/norman_database_system/
    
    echo "⬇️ Pulling latest changes on server..."
    git pull
    
    echo "🏗️ Building assets..."
    docker exec -it nds-app npm run build
    
    echo "🧹 Clearing Laravel views cache..."
    docker exec -it nds-app php artisan view:clear
    
    echo "✅ Server deployment completed!"
EOF

if [ $? -eq 0 ]; then
    echo "🎉 Deployment completed successfully!"
else
    echo "❌ Deployment failed on server."
    exit 1
fi