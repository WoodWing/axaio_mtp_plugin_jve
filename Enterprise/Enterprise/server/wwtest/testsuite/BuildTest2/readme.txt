---------------------
BuildTest2 test suite
---------------------

See TestSuite.php for test goals and methods.

This test suite respects the structure below. Every indented node represents a test suite.
For each node a simple explanation is given. 

	BuildTest2
			L> automatic testing module (to be shipped separately)
		TestSet#001
				L> container that builds its own environment (DB, filestore, system options, etc etc)
			2. System setup
					L> create DB, filestore, system options, etc
			5. Domain setup
					L> create brands, users, workflow, etc
			8. Features
					L> test functionality, feature wise
				Object locking (=TestCase)
				Personal status (=TestCase)
					TBD: system setup => preconditions
					testService001 (=function)
						100 max new rows (each table)
					testService002
					...
					testService999 (max functions)
				...
				999 (max test cases)
			15. Bug fixes
				BZ#12321
				BZ#24129
				...
				999 (max test cases)
			...
			19 (max areas)
		TestSet#002
		...

Currently, we can only store one test set due to MySQL limitation (see below).
When we'd change int to bigint for all id fields, we could store zillions of test sets.

For each TestSet, a new database and filestore is created.
Each TestSet takes the following record space in the database:
	Max rows = base of 100 000 000 + (20 areas x 1000 scenarios x 1000 functions x 100 rows)
	= 2 100 000 000
Max int = 2 147 483 648, which is the limit for id fields in MySQL, so just enough to fit one test set.

Explanation on the auto increment:
	Initial Auto Increment:
		We planned to start the db Id at 100,000,000 for recording purposes.
		Base = 100,000,000;

	AreaPrio:
		There are two areas which are 'Features' and 'BZIssues', each having their own folder that looks like below:
		/server/wwtest/testsuite/BuildTest2/TestSet001/Features/TestSuite.php
		/server/wwtest/testsuite/BuildTest2/TestSet001/Bug fixes/TestSuite.php

		Each having their own AreaPrio defined in getPrio(), so AreaPrio is retrieved via
		areaPrio = WW_TestSuite_BuildTest2_TestSet001_Features_TestSuite->getPrio();
	
	TestCasePrio:
		That's total file that ends with xxxx_TestCase.php in folder /server/wwtest/testsuite/BuildTest2/TestSet001/<<AREA>/ plus one, with totalTestCase file starting from Zero(no file yet).
		testCasePrio = total test case php file + 1;

	Function Number:
		Each service call during the recording is transformed into a function call in TestCase file and it is tracked with number starting from 1, incremented by 1 for each new upcoming
		service call.
		fxNumber = serviceCall number starting from 1;

	So, all together, Calculation of the auto increment to start for each function goes as follows:
	Base + 
	( areaPrio * 100,000,000 ) +  // 1000 testCases *  1000 serviceCall/fx * 100records
	( testCasePrio * 100,000 ) +  //  We allow up to 1000 testCases * 100 records per fx
	( fxNumber * 100rows )        //  Each function we allow 100 rows of records

