<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  
  <xsl:import href="form-functions.xsl"/>
  
  <xsl:param name="action"/>
  <xsl:param name="method">post</xsl:param>
  
  <xsl:template match="/*">
    <form method="{$method}" action="{$action}" enctype="multipart/form-data">
      
      <xsl:apply-templates select="lc:get-model(*[1])/lc:annotations/lc:message"/>
      <xsl:variable name="element" select="lc:get-root-element(current()/*[1])"/>
      
      <xsl:apply-templates select="*[1]/@*" mode="field">
        <xsl:with-param name="parent-element" select="$element"/>
      </xsl:apply-templates>
      
      <xsl:apply-templates select="*[1]/*" mode="field">
        <xsl:with-param name="parent-element" select="$element"/>
      </xsl:apply-templates>
      
      <xsl:apply-templates select="*[1]" mode="notice"/>
      
      <div class="field-actions">
        <input type="submit" value="Enregistrer"/>
        <input type="button" value="Annuler" onclick="history.go(-1);"/>
      </div>
      
    </form>
  </xsl:template>
  
</xsl:stylesheet>
