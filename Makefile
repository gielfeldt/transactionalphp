all: clean test coverage

clean:
	rm -rf build/artifacts/*

test:
	phpunit --testsuite=transactionalphp $(TEST)

coverage:
	phpunit --testsuite=transactionalphp --coverage-html=build/artifacts/coverage $(TEST)

coverage-show:
	open build/artifacts/coverage/index.html
