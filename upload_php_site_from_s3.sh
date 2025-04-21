#!/bin/bash

# ✅ Hosting PHP Website on AWS EC2 Using S3 (Without FileZilla)

# 📦 Step 1: Install AWS CLI on EC2 Ubuntu
sudo apt update
sudo apt install awscli -y

# If awscli package is not found, install using pip
sudo apt install python3-pip -y
pip3 install awscli --upgrade --user

# 🔐 Step 2: Configure AWS CLI
aws configure  # Enter Access Key ID, Secret Access Key, Region, Output format

# 🔐 Step 3: Copy AWS CLI credentials for root (if needed)
sudo mkdir -p /root/.aws
sudo cp ~/.aws/credentials /root/.aws/
sudo cp ~/.aws/config /root/.aws/

# 📁 Step 4: Copy website files from S3 to local folder
aws s3 cp s3://my-php-website-bucket/ ./mywebfiles --recursive

# 📁 Step 5: Move files to Apache web root
sudo cp -r ./mywebfiles/* /var/www/html/

# 🛠 Step 6: Set proper permissions for web files
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# 🌐 Step 7: Start Apache Server (if not running)
sudo systemctl start apache2

# ✅ Done! Access your site via http://<your-ec2-public-ip>
