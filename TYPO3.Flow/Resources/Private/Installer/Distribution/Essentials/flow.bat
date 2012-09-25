@ECHO OFF

REM Replace the following line with the path to your PHP executable if required
REM This can include the php.ini to be used (for example "SET PHP=C:/php/php.exe -c C:/php/php.ini")

SET PHP=php.exe

%PHP% Packages/Framework/TYPO3.Flow/Scripts/flow.php %*