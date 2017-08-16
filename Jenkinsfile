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
                deploy: [
                    stackname: 'medium--end2end',
                    revision: commit,
                    folder: '/srv/medium'
                ],
                marker: 'medium'
            )
        }

        stage 'Deploy to continuumtest', {
            lock('medium--continuumtest') {
                builderDeployRevision 'medium--continuumtest', commit
                builderSmokeTests 'medium--continuumtest', '/srv/medium'
            }
        }

        stage 'Approval', {
            elifeGitMoveToBranch commit, 'approved'
        }
    }
}
