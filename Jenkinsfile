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
            elifeSpectrum(
                preliminaryStep: {
                    builderDeployRevision 'medium--end2end', commit
                    builderSmokeTests 'medium--end2end', '/srv/medium'
                },
                rollbackStep: {
                    builderDeployRevision 'medium--end2end', 'approved'
                    builderSmokeTests 'medium--end2end', '/srv/medium'
                },
                marker: 'medium'
            )
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
