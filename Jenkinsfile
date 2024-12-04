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
        S3_BUCKET = 'your-s3-bucket' // Your S3 bucket where the application versions will be uploaded
    }

    stages {
        stage('Checkout') {
            steps {
                // Checkout code from GitHub repository
                git branch: "${BRANCH_NAME}", url: "${GITHUB_REPO}"

                // Verify the commit hash for debugging purposes
                sh "git log -n 1 --oneline" // Shows the latest commit hash
            }
        }

        stage('Create New Version') {
            steps {
                script {
                    // Generate a version label based on the Jenkins build number (or timestamp)
                    def versionLabel = "v${BUILD_NUMBER}"

                    // Create a new application version in Elastic Beanstalk directly from the GitHub repository
                    sh """
                    aws elasticbeanstalk create-application-version \
                        --application-name ${APPLICATION_NAME} \
                        --version-label ${versionLabel} \
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
