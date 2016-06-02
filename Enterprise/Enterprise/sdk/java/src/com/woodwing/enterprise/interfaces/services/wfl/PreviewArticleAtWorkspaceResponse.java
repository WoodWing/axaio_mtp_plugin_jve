/**
 * PreviewArticleAtWorkspaceResponse.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PreviewArticleAtWorkspaceResponse  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements;

    private com.woodwing.enterprise.interfaces.services.wfl.Element[] elements;

    private com.woodwing.enterprise.interfaces.services.wfl.Page[] pages;

    private java.lang.String layoutVersion;

    private com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles;

    private com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations;

    public PreviewArticleAtWorkspaceResponse() {
    }

    public PreviewArticleAtWorkspaceResponse(
           com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements,
           com.woodwing.enterprise.interfaces.services.wfl.Element[] elements,
           com.woodwing.enterprise.interfaces.services.wfl.Page[] pages,
           java.lang.String layoutVersion,
           com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles,
           com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
           this.placements = placements;
           this.elements = elements;
           this.pages = pages;
           this.layoutVersion = layoutVersion;
           this.inDesignArticles = inDesignArticles;
           this.relations = relations;
    }


    /**
     * Gets the placements value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return placements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Placement[] getPlacements() {
        return placements;
    }


    /**
     * Sets the placements value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param placements
     */
    public void setPlacements(com.woodwing.enterprise.interfaces.services.wfl.Placement[] placements) {
        this.placements = placements;
    }


    /**
     * Gets the elements value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return elements
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Element[] getElements() {
        return elements;
    }


    /**
     * Sets the elements value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param elements
     */
    public void setElements(com.woodwing.enterprise.interfaces.services.wfl.Element[] elements) {
        this.elements = elements;
    }


    /**
     * Gets the pages value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return pages
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Page[] getPages() {
        return pages;
    }


    /**
     * Sets the pages value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param pages
     */
    public void setPages(com.woodwing.enterprise.interfaces.services.wfl.Page[] pages) {
        this.pages = pages;
    }


    /**
     * Gets the layoutVersion value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return layoutVersion
     */
    public java.lang.String getLayoutVersion() {
        return layoutVersion;
    }


    /**
     * Sets the layoutVersion value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param layoutVersion
     */
    public void setLayoutVersion(java.lang.String layoutVersion) {
        this.layoutVersion = layoutVersion;
    }


    /**
     * Gets the inDesignArticles value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return inDesignArticles
     */
    public com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] getInDesignArticles() {
        return inDesignArticles;
    }


    /**
     * Sets the inDesignArticles value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param inDesignArticles
     */
    public void setInDesignArticles(com.woodwing.enterprise.interfaces.services.wfl.InDesignArticle[] inDesignArticles) {
        this.inDesignArticles = inDesignArticles;
    }


    /**
     * Gets the relations value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @return relations
     */
    public com.woodwing.enterprise.interfaces.services.wfl.Relation[] getRelations() {
        return relations;
    }


    /**
     * Sets the relations value for this PreviewArticleAtWorkspaceResponse.
     * 
     * @param relations
     */
    public void setRelations(com.woodwing.enterprise.interfaces.services.wfl.Relation[] relations) {
        this.relations = relations;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PreviewArticleAtWorkspaceResponse)) return false;
        PreviewArticleAtWorkspaceResponse other = (PreviewArticleAtWorkspaceResponse) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.placements==null && other.getPlacements()==null) || 
             (this.placements!=null &&
              java.util.Arrays.equals(this.placements, other.getPlacements()))) &&
            ((this.elements==null && other.getElements()==null) || 
             (this.elements!=null &&
              java.util.Arrays.equals(this.elements, other.getElements()))) &&
            ((this.pages==null && other.getPages()==null) || 
             (this.pages!=null &&
              java.util.Arrays.equals(this.pages, other.getPages()))) &&
            ((this.layoutVersion==null && other.getLayoutVersion()==null) || 
             (this.layoutVersion!=null &&
              this.layoutVersion.equals(other.getLayoutVersion()))) &&
            ((this.inDesignArticles==null && other.getInDesignArticles()==null) || 
             (this.inDesignArticles!=null &&
              java.util.Arrays.equals(this.inDesignArticles, other.getInDesignArticles()))) &&
            ((this.relations==null && other.getRelations()==null) || 
             (this.relations!=null &&
              java.util.Arrays.equals(this.relations, other.getRelations())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getPlacements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPlacements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPlacements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getElements() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getElements());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getElements(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPages() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getPages());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getPages(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getLayoutVersion() != null) {
            _hashCode += getLayoutVersion().hashCode();
        }
        if (getInDesignArticles() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getInDesignArticles());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getInDesignArticles(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getRelations() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getRelations());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getRelations(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PreviewArticleAtWorkspaceResponse.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", ">PreviewArticleAtWorkspaceResponse"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("placements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Placements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Placement"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Placement"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("elements");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Elements"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Element"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Element"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("pages");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Pages"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Page"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Page"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("layoutVersion");
        elemField.setXmlName(new javax.xml.namespace.QName("", "LayoutVersion"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("inDesignArticles");
        elemField.setXmlName(new javax.xml.namespace.QName("", "InDesignArticles"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "InDesignArticle"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "InDesignArticle"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("relations");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Relations"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "Relation"));
        elemField.setMinOccurs(0);
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "Relation"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
