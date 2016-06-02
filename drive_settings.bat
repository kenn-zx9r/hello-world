@echo off
set STR_INPUT=Y

set /P STR_INPUT="ドライブ割り当てを行います。よろしいですか？（Y/N）[Y]： "
IF "%STR_INPUT%" == "y" (
    goto YES
) ELSE IF "%STR_INPUT%"=="Y" ( 
    goto YES
) ELSE (
    goto FAILURE
)

:YES
echo ドライブ割り当て中...
net use /delete t:
net use t: \\999.999.999.999\XXXX /user:XXX\XXXXXX XXXX
set /P STR_INPUT="ドライブ割り当てが正常に完了しました。（任意のキーで終了）"
exit /B 0

:FAILURE
echo 処理を中止しました。
timeout 2
exit /B 9
