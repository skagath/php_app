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
            }
        }

        stage('Deploy to Elastic Beanstalk') {
            steps {
                script {
                    // Deploy the application to the environment
                    sh '''
                    aws elasticbeanstalk update-environment \
                        --application-name ${APPLICATION_NAME} \
                        --environment-name ${ENVIRONMENT_NAME} \
                        --version-label ${BUILD_NUMBER} \
                        --region ${AWS_REGION}
                    '''
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