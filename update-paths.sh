#!/bin/bash
# Script to update path references in all moved module files

echo "Updating path references in module files..."

# Function to update paths in a file
update_paths() {
    local file=$1
    local depth=$2
    
    # Calculate relative path to root based on depth
    local root_path=""
    for ((i=0; i<depth; i++)); do
        root_path="../$root_path"
    done
    
    # Update require_once paths
    sed -i '' "s|require_once 'config/config.php';|require_once '${root_path}config/config.php';|g" "$file"
    sed -i '' "s|require_once 'classes/|require_once '${root_path}classes/|g" "$file"
    sed -i '' "s|require_once 'includes/|require_once '${root_path}includes/|g" "$file"
    
    # Update asset paths
    sed -i '' "s|href=\"assets/|href=\"${root_path}public/assets/|g" "$file"
    sed -i '' "s|src=\"assets/|src=\"${root_path}public/assets/|g" "$file"
    
    echo "Updated: $file"
}

# Update auth module files (depth 2)
for file in modules/auth/*.php; do
    [ -f "$file" ] && update_paths "$file" 2
done

# Update subscription module files (depth 2)
for file in modules/subscription/*.php; do
    [ -f "$file" ] && update_paths "$file" 2
done

# Update dashboard module files (depth 2)
for file in modules/dashboard/*.php; do
    [ -f "$file" ] && update_paths "$file" 2
done

# Update sales module files (depth 3)
for dir in modules/sales/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done

# Update purchases module files (depth 3)
for dir in modules/purchases/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done

# Update inventory module files (depth 3 for subdirs, 2 for settings.php)
for dir in modules/inventory/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done
[ -f "modules/inventory/settings.php" ] && update_paths "modules/inventory/settings.php" 2

# Update accounting module files (depth 3)
for dir in modules/accounting/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done

# Update HR module files (depth 3 for subdirs, 2 for settings.php)
for dir in modules/hr/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done
[ -f "modules/hr/settings.php" ] && update_paths "modules/hr/settings.php" 2

# Update CRM module files (depth 3)
for dir in modules/crm/*/; do
    for file in "$dir"*.php; do
        [ -f "$file" ] && update_paths "$file" 3
    done
done

# Update settings module files (depth 2)
for file in modules/settings/*.php; do
    [ -f "$file" ] && update_paths "$file" 2
done

# Update reports module files (depth 2)
for file in modules/reports/*.php; do
    [ -f "$file" ] && update_paths "$file" 2
done

echo "Path updates completed!"
