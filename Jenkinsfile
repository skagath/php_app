pipeline {
    agent any

    environment {
        AWS_REGION = 'us-east-1' // Change to your AWS region
        AWS_ACCESS_KEY_ID = credentials('AWS-CREDENDS') // Set AWS Access Key from Jenkins Credentials Store
        AWS_SECRET_ACCESS_KEY = credentials('AWS-CREDENDS') // Set AWS Secret Access Key from Jenkins Credentials Store
        APPLICATION_NAME = 'myphp-app' // Your Elastic Beanstalk Application Name
        ENVIRONMENT_NAME = 'Myphp-app-env' // Your Elastic Beanstalk Environment Name
        GITHUB_REPO = 'https://github.com/skagath/php_app.git' // Replace with your GitHub repository URL
        BRANCH_NAME = 'main' // Branch to deploy from, e.g., 'main' or 'master'
    }

    stages {
        stage('Checkout') {
            steps {
                // Checkout code from GitHub repository
                git branch: "${BRANCH_NAME}", url: "${GITHUB_REPO}"

                // Verify the commit hash for debugging purposes
                sh "git log -n 1 --oneline"
            }
        }

        stage('Create New Version') {
            steps {
                script {
                    // Create a unique version label for every deployment
                    def versionLabel = "v${BUILD_NUMBER}"

                    // Zip the application code and upload it to S3
                    sh """
                    zip -r app-${versionLabel}.zip .
                    aws s3 cp app-${versionLabel}.zip s3://your-s3-bucket/app-${versionLabel}.zip
                    """

                    // Create a new application version
                    sh """
                    aws elasticbeanstalk create-application-version \
                        --application-name ${APPLICATION_NAME} \
                        --version-label ${versionLabel} \
                        --source-bundle S3Bucket="your-s3-bucket",S3Key="app-${versionLabel}.zip" \
                        --region ${AWS_REGION}
                    """

                    // Set the version label to be used in the next stage
                    env.VERSION_LABEL = versionLabel
                }
            }
        }

        stage('Deploy to Elastic Beanstalk') {
            steps {
                script {
                    // Deploy the new application version to Elastic Beanstalk
                    sh """
                    aws elasticbeanstalk update-environment \
                        --application-name ${APPLICATION_NAME} \
                        --environment-name ${ENVIRONMENT_NAME} \
                        --version-label ${env.VERSION_LABEL} \
                        --region ${AWS_REGION}
                    """
                }
            }
        }
    }

    post {
        always {
            // Clean up or notify upon completion
            echo "Pipeline completed"
        }

        success {
            // Actions to take if deployment is successful
            echo "Deployment to Elastic Beanstalk successful!"
        }

        failure {
            // Actions to take if deployment fails
            echo "Deployment failed!"
        }
    }
}
