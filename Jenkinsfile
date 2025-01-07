pipeline {
    agent any

    environment {
        AWS_REGION = 'us-west-2'                        // AWS region
        AWS_ACCESS_KEY_ID = credentials('AWS-CREDENDS') // AWS Access Key ID from Jenkins Credentials
        AWS_SECRET_ACCESS_KEY = credentials('AWS-CREDENDS') // AWS Secret Access Key from Jenkins Credentials
        APPLICATION_NAME = 'myphp-app'                  // Elastic Beanstalk Application Name
        ENVIRONMENT_NAME = 'Myphp-app-env'              // Elastic Beanstalk Environment Name
        GITHUB_REPO = 'https://github.com/skagath/php_app.git' // GitHub repository URL
        BRANCH_NAME = 'main'                            // Branch to deploy from
        S3_BUCKET = 'elasticbeanstalk-us-west-2-940482429350' // S3 bucket for deployment artifacts
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
                    --option-settings "Namespace=aws:elasticbeanstalk:command,OptionName=DeploymentPolicy,Value=Immutable"
                '''
            }
        }

        stage('Wait for Deployment Completion') {
            steps {
                echo "Waiting for the event: 'Environment update completed successfully.'"
                script {
                    def retries = 0
                    def maxRetries = 60  // Maximum retries (e.g., wait for 30 minutes with 30s intervals)
                    def eventFound = false

                    while (retries < maxRetries) {
                        // Fetch the latest Elastic Beanstalk events
                        def event = sh(script: """
                            aws elasticbeanstalk describe-events \
                                --application-name $APPLICATION_NAME \
                                --environment-name $ENVIRONMENT_NAME \
                                --region $AWS_REGION \
                                --max-items 5 \
                                --query "Events[?contains(Message, 'Environment update completed successfully.')].Message" \
                                --output text
                        """, returnStdout: true).trim()

                        echo "Checking for deployment completion event..."

                        if (event.contains('Environment update completed successfully')) {
                            echo "Deployment completed successfully!"
                            eventFound = true
                            break
                        }

                        retries++
                        if (retries >= maxRetries) {
                            error "Deployment event not found after maximum retries."
                        }

                        // Wait for 30 seconds before checking again
                        sleep(time: 30, unit: 'SECONDS')
                    }

                    if (!eventFound) {
                        error "Failed to detect 'Environment update completed successfully.' event."
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
