elifePipeline {
    def commit;

    stage 'Checkout', {
        checkout scm
        commit = elifeGitRevision()
    }

    stage 'Project tests', {
        lock('medium--ci') {
            builderDeployRevision 'medium--ci', commit
            builderProjectTests 'medium--ci', '/srv/medium', ['/srv/medium/build/phpunit.xml']
        }
    }

    elifeMainlineOnly {
        stage 'End2end tests', {
            elifeEnd2endTest({
                builderDeployRevision 'medium--end2end', commit
                builderSmokeTests 'medium--end2end', '/srv/medium'
            }, 'medium')
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
