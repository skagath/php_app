pipeline {
    agent any

    environment {
        AWS_REGION = 'us-east-1' // Change to your AWS region
        AWS_ACCESS_KEY_ID = credentials('AWS-ACCESS-KEY-ID') // AWS Access Key ID from Jenkins Credentials
        AWS_SECRET_ACCESS_KEY = credentials('AWS-SECRET-ACCESS-KEY') // AWS Secret Access Key from Jenkins Credentials
        APPLICATION_NAME = 'myphp-app' // Your Elastic Beanstalk Application Name
        ENVIRONMENT_NAME = 'Myphp-app-env' // Your Elastic Beanstalk Environment Name
        GITHUB_REPO = 'https://github.com/skagath/php_app.git' // Replace with your GitHub repository URL
        BRANCH_NAME = 'main' // Branch to deploy from
        S3_BUCKET = 'php-elastic-beanstalk-app' // Your S3 bucket for deployment artifacts
    }

    stages {
        stage('Checkout Code') {
            steps {
                echo "Cloning repository ${GITHUB_REPO} (branch: ${BRANCH_NAME})"
                git branch: "${BRANCH_NAME}", url: "${GITHUB_REPO}"
            }
        }

        stage('Package Application') {
            steps {
                echo "Packaging application into a ZIP file..."
                sh '''
                zip -r application.zip * .[^.]* -x Jenkinsfile
                '''
            }
        }

        stage('Upload to S3') {
            steps {
                echo "Uploading application.zip to S3 bucket ${S3_BUCKET}..."
                sh '''
                aws s3 cp application.zip s3://$S3_BUCKET/application-${BUILD_NUMBER}.zip --region $AWS_REGION
                '''
            }
        }

        stage('Create New Application Version') {
            steps {
                echo "Creating a new application version in Elastic Beanstalk..."
                sh '''
                aws elasticbeanstalk create-application-version \
                    --application-name $APPLICATION_NAME \
                    --version-label build-${BUILD_NUMBER} \
                    --source-bundle S3Bucket=$S3_BUCKET,S3Key=application-${BUILD_NUMBER}.zip \
                    --region $AWS_REGION
                '''
            }
        }

        stage('Deploy to Elastic Beanstalk') {
            steps {
                echo "Updating Elastic Beanstalk environment ${ENVIRONMENT_NAME} to use new version..."
                sh '''
                aws elasticbeanstalk update-environment \
                    --environment-name $ENVIRONMENT_NAME \
                    --version-label build-${BUILD_NUMBER} \
                    --region $AWS_REGION
                '''
            }
        }
    }

    post {
        always {
            echo "Pipeline execution completed."
        }

        success {
            echo "Deployment to Elastic Beanstalk was successful!"
        }

        failure {
            echo "Deployment failed. Please check the logs for more details."
        }
    }
}
