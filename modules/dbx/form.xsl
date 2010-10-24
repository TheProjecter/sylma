<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns="http://www.w3.org/1999/xhtml" xmlns:func="http://exslt.org/functions" xmlns:lc="http://www.sylma.org/schemas" xmlns:lx="http://ns.sylma.org/xslt" version="1.0" extension-element-prefixes="func lx">
  <xsl:import href="../../schemas/functions.xsl"/>
  <xsl:import href="/sylma/xslt/string.xsl"/>
  <xsl:param name="action"/>
  <xsl:param name="method" select="'POST'"/>
  <xsl:template match="/*">
    <form method="{$method}" action="{$action}">
      <xsl:apply-templates select="lc:get-model(*[1])/lc:annotations/lc:message"/>
      <xsl:apply-templates select="*[1]/@*"/>
      <xsl:apply-templates select="*[1]/*[not(@lc:editable = 'false')]" mode="field"/>
      <div class="field-actions">
        <input type="submit" value="Enregistrer"/>
        <input type="button" value="Annuler" onclick="history.go(-1);"/>
      </div>
    </form>
  </xsl:template>
  <xsl:template match="*" mode="field">
    <xsl:variable name="name" select="lc:get-name()"/>
    <xsl:variable name="id" select="concat('field-', lc:get-name())"/>
    <xsl:variable name="statut" select="concat('field-statut-', lc:get-statut())"/>
    <xsl:variable select="'field-input-element'" name="class"/>
    <div class="field clear-block {$statut}">
      <xsl:apply-templates select="." mode="label">
        <xsl:with-param name="id" select="$id"/>
      </xsl:apply-templates>
      <xsl:apply-templates select="." mode="input">
        <xsl:with-param name="id" select="$id"/>
        <xsl:with-param name="name" select="$name"/>
        <xsl:with-param name="class" select="$class"/>
      </xsl:apply-templates>
      <xsl:apply-templates select="@*"/>
      <xsl:apply-templates select="lc:get-model()/lc:annotations/lc:message"/>
    </div>
  </xsl:template>
  <xsl:template match="*" mode="label">
    <xsl:param name="id"/>
    <label for="{$id}">
      <xsl:value-of select="lx:first-case(lc:get-title())"/>
      <xsl:text> :</xsl:text>
    </label>
  </xsl:template>
  <xsl:template match="*" mode="input">
    <xsl:param name="id"/>
    <xsl:param name="name"/>
    <xsl:param name="class"/>
    <xsl:choose>
      <xsl:when test="not(lc:get-model())">
        <textarea name="{$name}" id="{$id}" style="background-color: #eee" class="{$class}">
          <xsl:value-of select="."/>
        </textarea>
      </xsl:when>
      <xsl:when test="lc:is-string()">
        <xsl:choose>
          <xsl:when test="lc:is-enum()">
            <select name="{$name}" id="{$id}" class="{$class}">
              <option value="0">&lt; choisissez &gt;</option>
              <xsl:apply-templates select="lc:get-schema()/lc:restriction/lc:enumeration">
                <xsl:with-param name="value" select="node()"/>
              </xsl:apply-templates>
            </select>
          </xsl:when>
          <xsl:when test="@lc:line-break">
            <textarea id="{$id}" name="{$name}" class="{$class}">
              <xsl:value-of select="."/>
            </textarea>
          </xsl:when>
          <xsl:otherwise>
            <input type="text" value="{.}" name="{$name}" id="{$id}" class="{$class}"/>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:when>
      <xsl:when test="lc:is-date()">
        <input type="hidden" value="{$id};;{$name};;{.}" class="{$class} field-input-date"/>
        <span id="{$id}" class="field-input-date"/>
      </xsl:when>
      <xsl:otherwise>
        <textarea id="{$id}" name="{$name}" class="{$class}">
          <xsl:value-of select="."/>
        </textarea>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
  <xsl:template match="@*">
    <xsl:if test="namespace-uri() != 'http://www.sylma.org/schemas'">
      <input type="hidden" value="{.}" name="attr-{local-name()}"/>
    </xsl:if>
  </xsl:template>
  <xsl:template match="lc:enumeration">
    <xsl:param name="value"/>
    <option>
      <xsl:if test="$value = text()">
        <xsl:attribute name="selected">selected</xsl:attribute>
      </xsl:if>
      <xsl:value-of select="."/>
    </option>
  </xsl:template>
  <xsl:template match="lc:message">
    <div class="field-message">
      <xsl:copy-of select="node()"/>
    </div>
  </xsl:template>
</xsl:stylesheet>
