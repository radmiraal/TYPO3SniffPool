<?php
/**
 * TYPO3_Sniffs_WhiteSpace_DisallowSpaceIndentSniff.
 *
 * PHP version 5
 * TYPO3 version 4
 *
 * @category  Whitespace
 * @package   TYPO3_PHPCS_Pool
 * @author    Stefano Kowalke <blueduck@gmx.net>
 * @copyright 2010 Stefano Kowalke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @link      http://pear.typo3.org
 */
/**
 * Checks that code is indent with tabs; spaces are not allowed.
 *
 * @category  Whitespace
 * @package   TYPO3_PHPCS_Pool
 * @author    Stefano Kowalke <blueduck@gmx.net>
 * @copyright 2010 Stefano Kowalke
 * @license   http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @version   Release: @package_version@
 * @link      http://pear.typo3.org
 */
class typo3pool_Sniffs_WhiteSpace_DisallowSpaceIndentSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('PHP');

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        return array(T_OPEN_TAG);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        // Make sure this is the first open tag.
        $previousOpenTag = $phpcsFile->findPrevious(array(T_OPEN_TAG), ($stackPtr - 1));
        if ($previousOpenTag !== false) {
            return;
        }
        $tokenCount = 0;
        $currentLineContent = '';
        $currentLine = 1;
        $tokenIsDocComment = true;
        $tokenIsString = true;
        foreach ($tokens as $token) {
            $tokenCount++;
            if ($token['line'] === $currentLine) {
                $currentLineContent.= $token['content'];
            } else {
                $currentLineContent = trim($currentLineContent, $phpcsFile->eolChar);
                $this->ifSpaceIndent($phpcsFile, ($tokenCount - 1), $currentLineContent, $tokenIsDocComment, $tokenIsString);
                $currentLineContent = $token['content'];
                // We have to check if the current token is a comment.
                // We are looking for doc comments and normal comments
                // but by the architecture comments like ...
                // "// comment" will be ignored
                $tokenIsDocComment = preg_match('/^T_(DOC_)?COMMENT$/', $token['type']) ? true : false;
                $tokenIsString = preg_match('/^T_CONSTANT_ENCAPSED_STRING$/', $token['type']) ? true : false;
                $currentLine++;
            }
        }
        $this->ifSpaceIndent($phpcsFile, ($tokenCount - 1), $currentLineContent, (bool) $tokenIsDocComment, (bool) $tokenIsString);
        return;
    }

    /**
     * Check if the code is intend with spaces
     *
     * @param PHP_CodeSniffer_File $phpcsFile         The file being scanned.
     * @param int                  $stackPtr          The token at the end of the line.
     * @param string               $lineContent       The content of the line.
     * @param boolean              $tokenIsDocComment True if the token is a doc block comment. False otherwise
     * @param boolean              $tokenIsString     True if the token is a string. False otherwise.
     *
     * @return void
     */
    protected function ifSpaceIndent(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $lineContent, $tokenIsDocComment, $tokenIsString)
    {
        // is the line intent by something?
        $hasIndention = preg_match('/(^\S)|(^\s\*)|(^$)/', $lineContent) ? false : true;
        $indentionPart = '';
        if ($hasIndention) {
             // spaces in strings at line start are allowed, so we don't care about
            if ($tokenIsString) {
                return;
            }

            if ($tokenIsDocComment) {
                $indentionPart = (string) substr($lineContent, 0, strpos($lineContent, ' *'));
            } else {
                // get the intention part of the line
                // (is stored in $matches)
                preg_match_all('/^\s+/', $lineContent, $matches);
                $indentionPart = $matches[0][0];
            }
            // is a space char in the indention?
            $isSpace = preg_match('/[^\t]/', $indentionPart) ? true : false;
            if ($isSpace) {
                $error = 'Tabs must be used to indent lines; spaces are not allowed';
                $phpcsFile->addError($error, $stackPtr - 1, ' http://forge.typo3.org/projects/team-php_codesniffer/wiki/Whitespace#Indent-code');
            }
        }
    }
}
?>