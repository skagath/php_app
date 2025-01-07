pipeline {
    agent any

    environment {
        AWS_REGION = 'us-west-2'                       // Change to your AWS region
        AWS_ACCESS_KEY_ID = credentials('AWS-CREDENDS') // AWS Access Key ID from Jenkins Credentials
        AWS_SECRET_ACCESS_KEY = credentials('AWS-CREDENDS') // AWS Secret Access Key from Jenkins Credentials
        APPLICATION_NAME = 'myphp-app'                // Your Elastic Beanstalk Application Name
        ENVIRONMENT_NAME = 'Myphp-app-env'            // Your Elastic Beanstalk Environment Name
        GITHUB_REPO = 'https://github.com/skagath/php_app.git' // Replace with your GitHub repository URL
        BRANCH_NAME = 'main'                          // Branch to deploy from
        S3_BUCKET = 'elasticbeanstalk-us-west-2-940482429350' // Your S3 bucket for deployment artifacts
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
                echo "Updating Elastic Beanstalk environment ${ENVIRONMENT_NAME} to use new version (Rolling Update)..."
                sh '''
                aws elasticbeanstalk update-environment \
                    --environment-name $ENVIRONMENT_NAME \
                    --version-label build-${BUILD_NUMBER} \
                    --region $AWS_REGION \
                    --option-settings "Namespace=aws:elasticbeanstalk:command,OptionName=DeploymentPolicy,Value=Rolling"
                '''
            }
        }

        stage('Wait for Deployment Completion') {
            steps {
                echo "Waiting for deployment to complete using event and health validation..."
                script {
                    def retries = 0
                    def maxRetries = 60 // Wait up to 30 minutes (60 retries, 30s each)
                    def deploymentSuccess = false

                    while (retries < maxRetries) {
                        // Check Elastic Beanstalk environment health
                        def healthStatus = sh(script: """
                            aws elasticbeanstalk describe-environments \
                                --application-name $APPLICATION_NAME \
                                --environment-name $ENVIRONMENT_NAME \
                                --region $AWS_REGION \
                                --query "Environments[0].Health" \
                                --output text
                        """, returnStdout: true).trim()

                        echo "Current Environment Health Status: $healthStatus"

                        // Check Elastic Beanstalk events for successful deployment message
                        def deploymentEvent = sh(script: """
                            aws elasticbeanstalk describe-events \
                                --application-name $APPLICATION_NAME \
                                --environment-name $ENVIRONMENT_NAME \
                                --region $AWS_REGION \
                                --max-items 5 \
                                --query "Events[?contains(Message, 'Environment update completed successfully.')].Message" \
                                --output text
                        """, returnStdout: true).trim()

                        echo "Checking for 'Environment update completed successfully' event..."

                        if (healthStatus == 'Green' && deploymentEvent.contains('Environment update completed successfully')) {
                            echo "Deployment completed successfully, environment is healthy."
                            deploymentSuccess = true
                            break
                        }

                        retries++
                        if (retries >= maxRetries) {
                            error "Deployment did not complete successfully after maximum retries."
                        }

                        sleep(time: 30, unit: 'SECONDS')
                    }

                    if (!deploymentSuccess) {
                        error "Failed to detect successful deployment or environment health after maximum retries."
                    }
                }
            }
        }
    }

    post {
        always {
            echo "Pipeline execution completed."
        }

        success {
            echo "✅ Deployment to Elastic Beanstalk was successful!"
        }

        failure {
            echo "❌ Deployment failed. Please check the logs for more details."
        }
    }
}
