pipeline {
    agent any

    environment {
        AWS_REGION = 'us-west-2'
        AWS_ACCESS_KEY_ID = credentials('AWS-CREDENDS')
        AWS_SECRET_ACCESS_KEY = credentials('AWS-CREDENDS')
        APPLICATION_NAME = 'myphp-app'
        ENVIRONMENT_NAME = 'Myphp-app-env'
        GITHUB_REPO = 'https://github.com/skagath/php_app.git'
        BRANCH_NAME = 'main'
        S3_BUCKET = 'elasticbeanstalk-us-west-2-940482429350'
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

        stage('Check Elastic Beanstalk Environment Health') {
            steps {
                script {
                    // Poll the Elastic Beanstalk environment health
                    def healthStatus = ''
                    def retries = 0
                    def maxRetries = 10
                    def success = false
                    
                    // Retry logic
                    while (retries < maxRetries && !success) {
                        healthStatus = sh(script: "aws elasticbeanstalk describe-environments --environment-names ${ENVIRONMENT_NAME} --region ${AWS_REGION} --query 'Environments[0].Health' --output text", returnStdout: true).trim()
                        echo "Current Health Status: ${healthStatus}"

                        if (healthStatus == 'Green') {
                            echo "Environment health is OK. Proceeding with rebuild."
                            success = true
                        } else {
                            echo "Environment health is not OK. Retrying in 30 seconds..."
                            retries++
                            sleep(30)
                        }
                    }

                    if (!success) {
                        error "Environment health is not OK after ${maxRetries} retries. Aborting rebuild."
                    }
                }
            }
        }

        stage('Rebuild Environment') {
            steps {
                echo "Rebuilding environment to include all updates (scaling groups, load balancers, etc.)..."
                sh '''
                aws elasticbeanstalk rebuild-environment --environment-name $ENVIRONMENT_NAME --region $AWS_REGION
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
