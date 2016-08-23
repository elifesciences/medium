elifePipeline {
    stage 'Checkout'
    checkout scm
    def commit = elifeGitRevision()

    stage 'Project tests'
    lock('medium--ci') {
        builderDeployRevision 'medium--ci', commit
        builderProjectTests 'medium--ci', '/srv/medium', ['/srv/medium/build/phpunit.xml']
    }

    //elifeMainlineOnly {
    //    stage 'End2end tests'

    //    stage 'Approval'
    //    elifeGitMoveToBranch commit, 'approved'
    //}
}
